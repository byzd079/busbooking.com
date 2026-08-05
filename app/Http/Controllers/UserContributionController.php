<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\BusBehaviorScore;
use App\Models\buslist;
use App\Models\BusPost;
use App\Models\Order;
use App\Models\PostComment;
use Illuminate\Support\Facades\Auth;

/**
 * "My contributions" — the signed-in passenger's own photos, replies and
 * trip ratings, plus the trips they have not rated yet.
 */
class UserContributionController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $email  = Auth::user()->email;

        $posts = BusPost::where('user_id', $userId)
            ->with('buslist:id,bus_name,coach_no')
            ->withCount('comments')
            ->latest()
            ->paginate(9, ['*'], 'posts_page');

        $scores = BusBehaviorScore::where('user_id', $userId)
            ->with('buslist:id,bus_name,coach_no')
            ->latest()
            ->paginate(10, ['*'], 'scores_page');

        $commentCount = PostComment::where('user_id', $userId)->count();
        $helpfulEarned = BusPost::where('user_id', $userId)->sum('helpful_count');

        // Confirmed bookings with no conduct score yet — the prompt to rate.
        // bus_behavior_scores.bus_id is a buslists.id but orders.bus_id is a
        // buses.id, so translate the rated buslist ids into the buses.id values
        // an order could actually hold before excluding them.
        $ratedBuslistIds = BusBehaviorScore::where('user_id', $userId)->pluck('bus_id')->all();

        $ratedBusIds = [];

        if ($ratedBuslistIds) {
            $ratedCoachNos = buslist::whereIn('id', $ratedBuslistIds)->pluck('coach_no');
            $ratedBusIds = Bus::whereIn('coach_no', $ratedCoachNos)->pluck('id')->all();
        }

        $unratedOrders = Order::where('email', $email)
            ->where('status', 'Processing')
            ->whereNotIn('bus_id', $ratedBusIds ?: [0])
            ->latest('id')
            ->limit(5)
            ->get();

        return view('community.my-contributions', [
            'posts'         => $posts,
            'scores'        => $scores,
            'commentCount'  => $commentCount,
            'helpfulEarned' => (int) $helpfulEarned,
            'unratedOrders' => $unratedOrders,
            'badge'         => $this->badgeFor((int) $helpfulEarned),
        ]);
    }

    /**
     * A light contribution tier based on how many people found this user's
     * photos helpful. Recognition only — it unlocks nothing.
     */
    private function badgeFor(int $helpful): array
    {
        return match (true) {
            $helpful >= 50 => ['label' => 'Gold contributor',   'color' => '#f59e0b', 'icon' => 'fa-award'],
            $helpful >= 20 => ['label' => 'Silver contributor', 'color' => '#94a3b8', 'icon' => 'fa-medal'],
            $helpful >= 5  => ['label' => 'Bronze contributor', 'color' => '#b45309', 'icon' => 'fa-certificate'],
            default        => ['label' => 'New contributor',    'color' => '#64748b', 'icon' => 'fa-seedling'],
        };
    }
}
