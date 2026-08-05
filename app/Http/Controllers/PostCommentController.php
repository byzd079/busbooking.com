<?php

namespace App\Http\Controllers;

use App\Models\BusPost;
use App\Models\CommentFlag;
use App\Models\Order;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostCommentController extends Controller
{
    /** Comments allowed per user per hour. */
    private const RATE_LIMIT = 30;

    /**
     * Comment thread for one post, rendered as a partial for the modal.
     */
    public function index($postId)
    {
        $post = BusPost::visible()->with('user:id,name')->findOrFail($postId);

        $comments = PostComment::visible()
            ->topLevel()
            ->where('post_id', $post->id)
            ->with([
                'user:id,name',
                'replies' => fn ($q) => $q->visible()->with('user:id,name')->oldest(),
            ])
            ->oldest()
            ->get();

        return view('community.partials.comment-thread', [
            'post'     => $post,
            'comments' => $comments,
        ]);
    }

    public function store(Request $request, $postId)
    {
        $post = BusPost::visible()->findOrFail($postId);

        $validated = $request->validate([
            'comment_text'      => 'required|string|max:1000',
            'parent_comment_id' => 'nullable|integer',
        ], [
            'comment_text.required' => 'Please write something before posting.',
            'comment_text.max'      => 'Please keep your reply under 1000 characters.',
        ]);

        $userId = Auth::id();

        $recent = PostComment::withTrashed()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= self::RATE_LIMIT) {
            throw ValidationException::withMessages([
                'comment_text' => 'You have posted a lot of replies in the past hour. Please try again later.',
            ]);
        }

        // Only allow replying to a comment that belongs to this post, and never
        // nest deeper than one level.
        $parentId = null;
        if (!empty($validated['parent_comment_id'])) {
            $parent = PostComment::visible()
                ->where('post_id', $post->id)
                ->whereNull('parent_comment_id')
                ->find($validated['parent_comment_id']);

            $parentId = $parent?->id;
        }

        DB::transaction(function () use ($post, $userId, $validated, $parentId) {
            PostComment::create([
                'post_id'               => $post->id,
                'user_id'               => $userId,
                'parent_comment_id'     => $parentId,
                'comment_text'          => $validated['comment_text'],
                'is_verified_passenger' => $this->isVerifiedPassenger($post->bus_id),
            ]);

            BusPost::where('id', $post->id)->increment('comment_count');
        });

        return back()->with('success', 'Your reply has been posted.');
    }

    public function update(Request $request, $commentId)
    {
        $comment = PostComment::findOrFail($commentId);

        if (!$comment->isOwnedBy(Auth::id())) {
            abort(403, 'You can only edit your own replies.');
        }

        if (!$comment->isEditable()) {
            return back()->with('error', 'Replies can only be edited within '
                . PostComment::EDIT_WINDOW_MINUTES . ' minutes of posting.');
        }

        $validated = $request->validate([
            'comment_text' => 'required|string|max:1000',
        ]);

        $comment->update(['comment_text' => $validated['comment_text']]);

        return back()->with('success', 'Your reply has been updated.');
    }

    public function destroy($commentId)
    {
        $comment = PostComment::findOrFail($commentId);

        if (!$comment->isOwnedBy(Auth::id())) {
            abort(403, 'You can only remove your own replies.');
        }

        DB::transaction(function () use ($comment) {
            $comment->delete();

            BusPost::where('id', $comment->post_id)
                ->where('comment_count', '>', 0)
                ->decrement('comment_count');
        });

        return back()->with('success', 'Your reply has been removed.');
    }

    public function flag($commentId)
    {
        $comment = PostComment::visible()->findOrFail($commentId);
        $userId = Auth::id();

        // One flag per person — see BusPostController::flag() for why.
        $alreadyFlagged = CommentFlag::where('comment_id', $comment->id)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyFlagged) {
            return back()->with('success', 'Thanks — this reply has been reported for review.');
        }

        DB::transaction(function () use ($comment, $userId) {
            CommentFlag::create([
                'comment_id' => $comment->id,
                'user_id'    => $userId,
            ]);

            $comment->increment('flag_count');

            if ($comment->fresh()->flag_count >= BusPost::FLAG_HIDE_THRESHOLD) {
                // is_hidden is intentionally outside $fillable, so assign it directly.
                $comment->is_hidden = true;
                $comment->save();
            }
        });

        return back()->with('success', 'Thanks — this reply has been reported for review.');
    }

    /**
     * orders carries no user_id, so a completed trip is matched on email. The
     * buslists.id → buses.id bridging lives in User::hasCompletedOrderFor(),
     * since orders.bus_id is a buses.id and these ids are not interchangeable.
     */
    private function isVerifiedPassenger(int $buslistId): bool
    {
        $user = Auth::user();

        return $user ? $user->hasCompletedOrderFor($buslistId) : false;
    }
}
