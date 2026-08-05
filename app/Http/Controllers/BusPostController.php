<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\buslist;
use App\Models\BusPost;
use App\Models\BusBehaviorScore;
use App\Models\Order;
use App\Models\PostFlag;
use App\Models\PostHelpfulMark;
use App\Models\SeatRating;
use App\Services\BusImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BusPostController extends Controller
{
    /** Uploads allowed per user per hour. */
    private const UPLOAD_RATE_LIMIT = 8;

    public function __construct(private BusImageProcessor $images)
    {
    }

    /**
     * Public gallery for one bus.
     *
     * The {bus} segment is a buses.id (that is what search results link with),
     * so it is resolved to the master buslists row the same way SeatRatingController
     * does it — via coach_no.
     */
    public function index(Request $request, $busId)
    {
        [$bus, $buslist] = $this->resolveBus($busId);

        $filter = $request->query('type');
        $validFilters = array_keys(BusPost::TYPES);

        $query = BusPost::visible()
            ->forBus($buslist->id)
            // Explicit column list so the image bytes stay out of the result set.
            // $hidden only keeps image_data out of toArray()/toJson() — the ORM
            // would still load every BLOB, i.e. 12 full-size images per page.
            // The <img> tags hit the bus.post.image route, which fetches its own row.
            ->select([
                'id',
                'bus_id',
                'user_id',
                'post_type',
                'caption',
                'helpful_count',
                'comment_count',
                'is_verified_passenger',
                'created_at',
            ])
            // withCount avoids a comments query per post when rendering the grid.
            ->withCount('comments')
            ->with('user:id,name');

        if ($filter && in_array($filter, $validFilters, true)) {
            $query->where('post_type', $filter);
        }

        $sort = $request->query('sort', 'recent');
        if ($sort === 'helpful') {
            $query->orderByDesc('helpful_count')->orderByDesc('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        $posts = $query->paginate(12)->withQueryString();

        // Which of these posts the viewer has already marked helpful, in one
        // query rather than one per card.
        $markedHelpful = $this->markedHelpfulIds($posts->pluck('id')->all());

        $counts = BusPost::visible()
            ->forBus($buslist->id)
            ->select('post_type', DB::raw('count(*) as total'))
            ->groupBy('post_type')
            ->pluck('total', 'post_type')
            ->all();

        $behavior = BusBehaviorScore::summaryFor($buslist->id);
        $seatRating = SeatRating::getBusRatingSummary($buslist->id);

        return view('community.gallery', [
            'bus'           => $bus,
            'buslist'       => $buslist,
            'posts'         => $posts,
            'counts'        => $counts,
            'totalPosts'    => array_sum($counts),
            'activeFilter'  => in_array($filter, $validFilters, true) ? $filter : null,
            'sort'          => $sort,
            'markedHelpful' => $markedHelpful,
            'behavior'      => $behavior,
            'seatRating'    => $seatRating,
            'canPost'       => Auth::check(),
        ]);
    }

    /**
     * Serve the stored image bytes.
     *
     * Images live in the database because Render's free tier wipes the disk on
     * every deploy, so they need a route rather than a static URL. The long
     * cache header matters: without it every gallery scroll re-hits PHP.
     */
    public function image($postId)
    {
        $post = BusPost::select('id', 'image_data', 'mime_type', 'updated_at')
            ->findOrFail($postId);

        return response($post->image_data)
            ->header('Content-Type', $post->mime_type)
            ->header('Cache-Control', 'public, max-age=31536000, immutable')
            ->header('Content-Security-Policy', "default-src 'none'; img-src 'self'")
            ->header('X-Content-Type-Options', 'nosniff')
            ->setEtag(md5($post->id . $post->updated_at));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bus_id'    => 'required|integer',
            'post_type' => 'required|string|in:' . implode(',', array_keys(BusPost::TYPES)),
            'caption'   => 'nullable|string|max:500',
            'image'     => 'required|file|mimetypes:image/jpeg,image/png,image/webp|max:8192',
        ], [
            'image.required'  => 'Please choose a photo to upload.',
            'image.mimetypes' => 'Please upload a JPG, PNG or WebP image.',
            'image.max'       => 'That image is larger than 8 MB. Please choose a smaller photo.',
            'caption.max'     => 'Please keep the caption under 500 characters.',
        ]);

        [, $buslist] = $this->resolveBus($validated['bus_id']);

        $userId = Auth::id();

        $recentUploads = BusPost::withTrashed()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentUploads >= self::UPLOAD_RATE_LIMIT) {
            throw ValidationException::withMessages([
                'image' => 'You have uploaded a lot of photos in the past hour. Please try again later.',
            ]);
        }

        try {
            $processed = $this->images->process($request->file('image'));
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['image' => $e->getMessage()]);
        }

        // A paid order for this bus earns the verified-passenger badge; anyone
        // signed in may still post without it.
        $order = $this->matchingOrder($buslist->id);

        BusPost::create([
            'bus_id'                => $buslist->id,
            'user_id'               => $userId,
            'order_id'              => $order?->id,
            'post_type'             => $validated['post_type'],
            'image_data'            => $processed['data'],
            'mime_type'             => $processed['mime'],
            'image_size'            => $processed['size'],
            'image_width'           => $processed['width'],
            'image_height'          => $processed['height'],
            'caption'               => $validated['caption'] ?? null,
            'is_verified_passenger' => $order !== null,
        ]);

        return redirect()
            ->route('bus.gallery', $validated['bus_id'])
            ->with('success', 'Thanks — your photo is now on the bus gallery.');
    }

    /**
     * Toggle the viewer's "helpful" mark and keep the denormalised tally in step.
     */
    public function toggleHelpful(Request $request, $postId)
    {
        $post = BusPost::visible()->findOrFail($postId);
        $userId = Auth::id();

        $marked = DB::transaction(function () use ($post, $userId) {
            $existing = PostHelpfulMark::where('post_id', $post->id)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                $existing->delete();
                // Floor at zero so a double-submit can never push the count negative.
                BusPost::where('id', $post->id)->where('helpful_count', '>', 0)
                    ->decrement('helpful_count');

                return false;
            }

            PostHelpfulMark::create(['post_id' => $post->id, 'user_id' => $userId]);
            BusPost::where('id', $post->id)->increment('helpful_count');

            return true;
        });

        $count = BusPost::where('id', $post->id)->value('helpful_count');

        if ($request->expectsJson()) {
            return response()->json(['marked' => $marked, 'helpful_count' => $count]);
        }

        return back();
    }

    public function destroy($postId)
    {
        $post = BusPost::findOrFail($postId);

        if (!$post->isOwnedBy(Auth::id())) {
            abort(403, 'You can only remove your own photos.');
        }

        $post->delete();

        return back()->with('success', 'Your photo has been removed.');
    }

    /**
     * Flag a post for moderator attention. Auto-hides once enough distinct
     * people have flagged it, so obvious abuse disappears before review.
     */
    public function flag($postId)
    {
        $post = BusPost::visible()->findOrFail($postId);
        $userId = Auth::id();

        // One flag per person. Without this a single account could flag the same
        // post FLAG_HIDE_THRESHOLD times and hide anyone's photo by itself.
        $alreadyFlagged = PostFlag::where('post_id', $post->id)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyFlagged) {
            return back()->with('success', 'Thanks — this photo has been reported for review.');
        }

        DB::transaction(function () use ($post, $userId) {
            PostFlag::create([
                'post_id' => $post->id,
                'user_id' => $userId,
            ]);

            $post->increment('flag_count');

            if ($post->fresh()->flag_count >= BusPost::FLAG_HIDE_THRESHOLD) {
                // Set directly rather than update([...]): is_hidden is deliberately
                // not in $fillable, so a mass-assignment write would be discarded.
                $post->is_hidden = true;
                $post->save();
            }
        });

        return back()->with('success', 'Thanks — this photo has been reported for review.');
    }

    /**
     * Resolve a buses.id into both the trip row and its master buslists row.
     *
     * Falls back to treating the id as a buslists.id so links from pages that
     * already work in buslist ids keep resolving.
     */
    private function resolveBus($id): array
    {
        $bus = Bus::find($id);

        if ($bus) {
            $buslist = buslist::where('coach_no', $bus->coach_no)->first();

            if ($buslist) {
                return [$bus, $buslist];
            }
        }

        $buslist = buslist::find($id);

        if (!$buslist) {
            abort(404, 'That bus could not be found.');
        }

        return [$bus ?? $buslist, $buslist];
    }

    /**
     * The signed-in user's completed order for this bus, if any.
     *
     * orders has no user_id column, so ownership is matched on email; 'Processing'
     * is the status SslCommerzPaymentController writes once payment succeeds.
     *
     * orders.bus_id holds a buses.id while $buslistId is a buslists.id — separate
     * tables — so bridge them on coach_no rather than comparing directly.
     */
    private function matchingOrder(int $buslistId): ?Order
    {
        $email = Auth::user()->email ?? null;

        if (!$email) {
            return null;
        }

        $buslist = buslist::find($buslistId);

        if (!$buslist) {
            return null;
        }

        $busIds = Bus::where('coach_no', $buslist->coach_no)->pluck('id');

        if ($busIds->isEmpty()) {
            return null;
        }

        return Order::where('email', $email)
            ->whereIn('bus_id', $busIds)
            ->where('status', 'Processing')
            ->latest('id')
            ->first();
    }

    /** @return array<int,true> post ids the viewer has marked helpful */
    private function markedHelpfulIds(array $postIds): array
    {
        if (!Auth::check() || empty($postIds)) {
            return [];
        }

        return PostHelpfulMark::where('user_id', Auth::id())
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->flip()
            ->map(fn () => true)
            ->all();
    }
}
