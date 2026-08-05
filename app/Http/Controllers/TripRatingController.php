<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\buslist;
use App\Models\BusBehaviorScore;
use App\Models\Order;
use App\Models\SeatRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The combined post-trip rating: one form that records the seat star into the
 * existing seat_ratings table and the five conduct dimensions into
 * bus_behavior_scores, so passengers are only asked once.
 */
class TripRatingController extends Controller
{
    /**
     * Show the rating form for a completed order.
     */
    public function showForm($orderId)
    {
        $order = Order::findOrFail($orderId);

        $this->authorizeOrder($order);

        $bus = Bus::find($order->bus_id);
        $buslist = $bus
            ? buslist::where('coach_no', $bus->coach_no)->first()
            : buslist::find($order->bus_id);

        if (!$buslist) {
            return redirect()->route('purchase_history')
                ->with('error', 'We could not find the bus for that booking.');
        }

        // ticketlist is a free-form text column holding the booked seat labels.
        $seats = $this->parseSeats($order->ticketlist);
        $tripDate = $bus->date ?? $order->created_at->toDateString();

        $existingBehavior = BusBehaviorScore::where('user_id', Auth::id())
            ->where('bus_id', $buslist->id)
            ->where('trip_date', $tripDate)
            ->first();

        $existingSeatRatings = SeatRating::where('user_id', Auth::id())
            ->where('bus_id', $buslist->id)
            ->where('trip_date', $tripDate)
            ->pluck('rating', 'seat_name')
            ->all();

        return view('community.rate-trip', [
            'order'               => $order,
            'bus'                 => $bus,
            'buslist'             => $buslist,
            'seats'               => $seats,
            'tripDate'            => $tripDate,
            'dimensions'          => BusBehaviorScore::DIMENSIONS,
            'existingBehavior'    => $existingBehavior,
            'existingSeatRatings' => $existingSeatRatings,
        ]);
    }

    /**
     * Save both halves of the form in one transaction.
     */
    public function store(Request $request)
    {
        $rules = [
            'order_id'     => 'required|integer',
            'trip_date'    => 'required|date',
            'comment'      => 'nullable|string|max:1000',
            'seat_ratings' => 'nullable|array',
            'seat_ratings.*' => 'nullable|integer|between:1,5',
        ];

        // Every conduct dimension is required — a partial score would skew the
        // averages in ways the card cannot represent honestly.
        foreach (array_keys(BusBehaviorScore::DIMENSIONS) as $key) {
            $rules[$key . '_score'] = 'required|integer|between:1,5';
        }

        $validated = $request->validate($rules, [
            'route_adherence_score.required' => 'Please rate whether the bus followed its route.',
            'punctuality_score.required'     => 'Please rate whether the bus ran on time.',
            'cleanliness_score.required'     => 'Please rate how clean the bus was.',
            'driver_behavior_score.required' => 'Please rate the driver and staff conduct.',
            'overall_score.required'         => 'Please give the trip an overall rating.',
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $this->authorizeOrder($order);

        $bus = Bus::find($order->bus_id);
        $buslist = $bus
            ? buslist::where('coach_no', $bus->coach_no)->first()
            : buslist::find($order->bus_id);

        if (!$buslist) {
            return back()->with('error', 'We could not find the bus for that booking.');
        }

        DB::transaction(function () use ($validated, $order, $buslist) {
            // updateOrCreate against the unique key means resubmitting the form
            // corrects the earlier score instead of failing on the constraint.
            BusBehaviorScore::updateOrCreate(
                [
                    'user_id'   => Auth::id(),
                    'bus_id'    => $buslist->id,
                    'trip_date' => $validated['trip_date'],
                ],
                [
                    'order_id'              => $order->id,
                    'route_adherence_score' => $validated['route_adherence_score'],
                    'punctuality_score'     => $validated['punctuality_score'],
                    'cleanliness_score'     => $validated['cleanliness_score'],
                    'driver_behavior_score' => $validated['driver_behavior_score'],
                    'overall_score'         => $validated['overall_score'],
                    'comment'               => $validated['comment'] ?? null,
                    'is_verified_passenger' => true,
                ]
            );

            // Seat stars are optional; write only the ones actually filled in.
            // seat_ratings.comment is NOT NULL, so reuse the trip comment.
            foreach ($validated['seat_ratings'] ?? [] as $seatName => $rating) {
                if (!$rating) {
                    continue;
                }

                SeatRating::updateOrCreate(
                    [
                        'user_id'   => Auth::id(),
                        'bus_id'    => $buslist->id,
                        'trip_date' => $validated['trip_date'],
                        'seat_name' => $seatName,
                    ],
                    [
                        'rating'  => $rating,
                        'comment' => $validated['comment'] ?? '',
                    ]
                );
            }
        });

        return redirect()
            ->route('purchase_history')
            ->with('success', 'Thanks — your rating helps other passengers choose.');
    }

    /**
     * Public conduct summary for one bus, used by the gallery and search pages.
     */
    public function summary($busId)
    {
        $bus = Bus::find($busId);
        $buslist = $bus
            ? buslist::where('coach_no', $bus->coach_no)->first()
            : buslist::find($busId);

        if (!$buslist) {
            return response()->json(['success' => false, 'message' => 'Bus not found'], 404);
        }

        return response()->json([
            'success' => true,
            'summary' => BusBehaviorScore::summaryFor($buslist->id),
        ]);
    }

    /**
     * Only the passenger who paid for a completed trip may rate it.
     *
     * orders has no user_id column, so ownership is matched on email. 'Processing'
     * is the status SslCommerzPaymentController sets once payment succeeds.
     */
    private function authorizeOrder(Order $order): void
    {
        if ($order->email !== Auth::user()->email) {
            abort(403, 'That booking belongs to a different account.');
        }

        if ($order->status !== 'Processing') {
            abort(403, 'You can only rate a trip after the booking is confirmed.');
        }
    }

    /**
     * ticketlist stores seat labels as free text. Accept the common separators
     * and fall back to an empty list rather than guessing wrongly.
     *
     * @return array<int,string>
     */
    private function parseSeats(?string $ticketlist): array
    {
        if (!$ticketlist) {
            return [];
        }

        $decoded = json_decode($ticketlist, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        $parts = preg_split('/[,;|\s]+/', trim($ticketlist), -1, PREG_SPLIT_NO_EMPTY);

        return array_slice($parts ?: [], 0, 12);
    }
}
