<?php

namespace App\Http\Controllers;

use App\Models\SeatSwap;
use App\Models\Order;
use App\Models\Bus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeatSwapController extends Controller
{
    /**
     * Show form to select target seat & passenger to request swap
     */
    public function showSwapForm($orderId)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to request a seat swap.');
        }

        $order = Order::findOrFail($orderId);
        
        // Verify ownership
        if ($order->email !== Auth::user()->email) {
            return redirect()->route('purchase_history')->with('error', 'Unauthorized access');
        }

        if (!$order->canSwapSeats()) {
            return redirect()->route('purchase_history')->with('error', 'Seat swapping is only allowed until 1 hour after the journey begins.');
        }

        $bus = Bus::findOrFail($order->bus_id);
        $mySeats = is_array(json_decode($order->ticketlist, true)) ? json_decode($order->ticketlist, true) : [$order->ticketlist];

        // Find all other orders on the SAME bus trip
        $otherOrders = Order::where('bus_id', $bus->id)
            ->where('id', '!=', $order->id)
            ->whereIn('status', ['Processing', 'Successful'])
            ->get();

        $availableTargetSeats = [];
        foreach ($otherOrders as $otherOrder) {
            $otherSeats = is_array(json_decode($otherOrder->ticketlist, true)) ? json_decode($otherOrder->ticketlist, true) : [$otherOrder->ticketlist];
            $otherUser = User::where('email', $otherOrder->email)->first();
            
            foreach ($otherSeats as $seatName) {
                $availableTargetSeats[] = [
                    'order_id' => $otherOrder->id,
                    'seat_name' => $seatName,
                    'passenger_name' => $otherOrder->name ?? 'Passenger',
                    'passenger_email' => $otherOrder->email,
                    'user_id' => $otherUser ? $otherUser->id : null,
                ];
            }
        }

        return view('seat_swap.form', compact('order', 'bus', 'mySeats', 'availableTargetSeats'));
    }

    /**
     * Store a new seat swap request
     */
    public function requestSwap(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to request a seat swap.');
        }

        $request->validate([
            'requester_order_id' => 'required|exists:orders,id',
            'requester_seat' => 'required|string',
            'target_order_id' => 'required|exists:orders,id',
            'target_seat' => 'required|string',
        ]);

        $requesterOrder = Order::findOrFail($request->requester_order_id);
        $targetOrder = Order::findOrFail($request->target_order_id);

        if ($requesterOrder->email !== Auth::user()->email) {
            return redirect()->route('purchase_history')->with('error', 'Unauthorized access');
        }

        if (!$requesterOrder->canSwapSeats()) {
            return redirect()->route('purchase_history')->with('error', 'Seat swapping is only allowed until 1 hour after the journey begins.');
        }

        $targetUser = User::where('email', $targetOrder->email)->first();

        // Check if there is already a pending swap request for this seat
        $existing = SeatSwap::where('requester_order_id', $requesterOrder->id)
            ->where('requester_seat', $request->requester_seat)
            ->where('target_seat', $request->target_seat)
            ->where('status', 'Pending')
            ->first();

        if ($existing) {
            return redirect()->route('seat.swap.list')->with('error', 'Swap request for this seat is already pending.');
        }

        SeatSwap::create([
            'bus_id' => $requesterOrder->bus_id,
            'requester_id' => Auth::id(),
            'requester_order_id' => $requesterOrder->id,
            'requester_seat' => $request->requester_seat,
            'target_user_id' => $targetUser ? $targetUser->id : null,
            'target_order_id' => $targetOrder->id,
            'target_seat' => $request->target_seat,
            'status' => 'Pending'
        ]);

        return redirect()->route('seat.swap.list')->with('success', 'Seat swap request sent successfully to passenger!');
    }

    /**
     * List all incoming & outgoing seat swap requests for logged in user
     */
    public function mySwapRequests()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view swap requests.');
        }

        $userEmail = Auth::user()->email;
        $userId = Auth::id();

        // Outgoing Requests (sent by me)
        $outgoingSwaps = SeatSwap::where('requester_id', $userId)
            ->with(['bus', 'targetUser', 'requesterOrder', 'targetOrder'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Incoming Requests (sent to me via target_user_id or target_order's email)
        $incomingSwaps = SeatSwap::where(function($q) use ($userId, $userEmail) {
                $q->where('target_user_id', $userId)
                  ->orWhereHas('targetOrder', function($oq) use ($userEmail) {
                      $oq->where('email', $userEmail);
                  });
            })
            ->with(['bus', 'requester', 'requesterOrder', 'targetOrder'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('seat_swap.index', compact('outgoingSwaps', 'incomingSwaps'));
    }

    /**
     * Officially accept a seat swap request
     */
    public function acceptSwap(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to respond to swap request.');
        }

        $swap = SeatSwap::findOrFail($id);
        $userEmail = Auth::user()->email;

        // Verify target ownership
        if ($swap->target_user_id !== Auth::id() && ($swap->targetOrder && $swap->targetOrder->email !== $userEmail)) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        if ($swap->status !== 'Pending') {
            return redirect()->back()->with('error', 'This swap request is no longer pending.');
        }

        $requesterOrder = Order::findOrFail($swap->requester_order_id);
        if (!$requesterOrder->canSwapSeats()) {
            return redirect()->back()->with('error', 'Seat swap deadline has passed. Swapping is only allowed until 1 hour after the journey begins.');
        }

        try {
            DB::beginTransaction();

            $requesterOrder = Order::findOrFail($swap->requester_order_id);
            $targetOrder = Order::findOrFail($swap->target_order_id);

            // Update Requester Order seats (replace requester_seat with target_seat)
            $reqSeats = is_array(json_decode($requesterOrder->ticketlist, true)) ? json_decode($requesterOrder->ticketlist, true) : [$requesterOrder->ticketlist];
            $reqKey = array_search($swap->requester_seat, $reqSeats);
            if ($reqKey !== false) {
                $reqSeats[$reqKey] = $swap->target_seat;
            }
            $requesterOrder->update(['ticketlist' => json_encode($reqSeats)]);

            // Update Target Order seats (replace target_seat with requester_seat)
            $targetSeats = is_array(json_decode($targetOrder->ticketlist, true)) ? json_decode($targetOrder->ticketlist, true) : [$targetOrder->ticketlist];
            $targetKey = array_search($swap->target_seat, $targetSeats);
            if ($targetKey !== false) {
                $targetSeats[$targetKey] = $swap->requester_seat;
            }
            $targetOrder->update(['ticketlist' => json_encode($targetSeats)]);

            // Update Swap Status to Accepted
            $swap->update(['status' => 'Accepted']);

            DB::commit();

            return redirect()->route('seat.swap.list')->with('success', 'Seat swap accepted! Both tickets have been updated officially.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to complete seat swap.');
        }
    }

    /**
     * Decline a seat swap request
     */
    public function declineSwap(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to respond.');
        }

        $swap = SeatSwap::findOrFail($id);
        $swap->update(['status' => 'Declined']);

        return redirect()->route('seat.swap.list')->with('success', 'Seat swap request declined.');
    }

    /**
     * Cancel an outgoing seat swap request
     */
    public function cancelSwap(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to cancel request.');
        }

        $swap = SeatSwap::findOrFail($id);
        if ($swap->requester_id === Auth::id()) {
            $swap->update(['status' => 'Cancelled']);
        }

        return redirect()->route('seat.swap.list')->with('success', 'Seat swap request cancelled.');
    }
}
