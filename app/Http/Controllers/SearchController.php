<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\Bus;
use App\Models\buslist;
use App\Models\BusBehaviorScore;
use App\Models\BusPost;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Order;
use App\Models\SeatRating;
use App\Models\SeatHold;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SearchController extends Controller
{
    /**
     * Attach photo counts and conduct scores to a set of buses.
     *
     * Both lookups are keyed on buslists.id, so the coach_no -> buslist mapping
     * is resolved once for the whole collection and the two aggregates are
     * fetched in one query each, rather than per row.
     */
    private function attachCommunityData($buses): void
    {
        if ($buses->isEmpty()) {
            return;
        }

        $buslistIds = buslist::whereIn('coach_no', $buses->pluck('coach_no')->unique())
            ->pluck('id', 'coach_no');

        $photoCounts = BusPost::visible()
            ->whereIn('bus_id', $buslistIds->values())
            ->selectRaw('bus_id, count(*) as total')
            ->groupBy('bus_id')
            ->pluck('total', 'bus_id');

        $behavior = BusBehaviorScore::averagesForBuses($buslistIds->values()->all());

        foreach ($buses as $bus) {
            $buslistId = $buslistIds[$bus->coach_no] ?? null;

            $bus->buslist_id   = $buslistId;
            $bus->photo_count  = $buslistId ? (int) ($photoCounts[$buslistId] ?? 0) : 0;
            $bus->behavior     = $buslistId ? ($behavior[$buslistId] ?? null) : null;
        }
    }

    public function search_bus(Request $request)
    {
        // Normalise every input up front. Trimming here (and TRIM() on the
        // column side below) makes route matching whitespace-insensitive.
        $date           = trim((string) $request->input('date'));
        $starting_point = trim((string) $request->input('starting_point'));
        $ending_point   = trim((string) $request->input('ending_point'));
        $bus_name       = trim((string) $request->input('bus_name'));

        // Nothing was asked for: show today's departures, best-rated first.
        if ($date === '' && $starting_point === '' && $ending_point === '' && $bus_name === '') {
            $today = Bus::where('date', Carbon::today()->toDateString())->get();

            return $this->presentBuses($today);
        }

        // A concrete journey date was chosen: materialise that day's inventory
        // from the master schedule before we search it.
        $query = Bus::query();

        if ($date !== '') {
            IfNotFoundThenCreate($date);
            $query->where('date', $date);
        }

        // Case- and whitespace-insensitive route matching. whereRaw with a
        // bound parameter keeps this portable (SQLite locally, Postgres in
        // production) and safe from injection — never ILIKE, never interpolation.
        if ($starting_point !== '') {
            $query->whereRaw('LOWER(TRIM(starting_point)) = ?', [strtolower($starting_point)]);
        }

        if ($ending_point !== '') {
            $query->whereRaw('LOWER(TRIM(ending_point)) = ?', [strtolower($ending_point)]);
        }

        // Partial, case-insensitive operator match. Standing alone (no route)
        // this lets a passenger find every trip a given operator runs.
        if ($bus_name !== '') {
            $query->whereRaw('LOWER(bus_name) LIKE ?', ['%' . strtolower($bus_name) . '%']);
        }

        return $this->presentBuses($query->get());
    }

    /**
     * Decorate a set of buses and hand off to the results view.
     *
     * Every search branch funnels through here so ratings, ordering and the
     * community data attach are applied identically. The view renders its own
     * empty state, so there is nothing to flash when the set comes back empty.
     */
    private function presentBuses($buses)
    {
        // Attach each bus's rating summary.
        foreach ($buses as $bus) {
            $bus->rating_data = $bus->getRatingSummary();
        }

        // Highest-rated first.
        $buses = $buses->sortByDesc(function ($bus) {
            return $bus->rating_data['average_rating'];
        })->values();

        $this->attachCommunityData($buses);

        return view('showbustable', compact('buses'));
    }
    // create a function named  'payment_details'
    public function payment_details(Request $request)
    {
        $bus_id = $request->input('id');
        $idx = 0;
        $bus = Bus::find($bus_id);
        for ($i = 'A'; $i <= 'Z'; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                $idx++;
                $checkboxNames[] = $i . $j;
                if ($idx == $bus->total_seats) {
                    break;
                }
            }
            if ($idx == $bus->total_seats) {
                break;
            }
        }
        $ticketlist = [];
        for ($i = 0; $i < count($checkboxNames); $i++) {
            if ($request->input($checkboxNames[$i]) != null) {
                $ticketlist[] = $checkboxNames[$i];
            }
        }
        if (!count($ticketlist)) {
            return redirect()->back()->with('error', 'Please select at least a seat and login!!');
        }

        // Clean up globally expired holds
        SeatHold::cleanupExpired();

        $seatIndexes = array_flip($checkboxNames);
        $view = $bus->view;

        // Check if any seat is already booked permanently in bus->view
        foreach ($ticketlist as $seat) {
            $index = $seatIndexes[$seat] ?? null;
            if ($index !== null && ($view[$index] ?? '1') !== '0') {
                return redirect()->route('seat_view', $bus->id)
                    ->with('error', "Seat {$seat} has already been booked. Please select another seat.");
            }
        }

        $sessionId = session()->getId();

        // Check if any selected seat is currently on hold by another customer
        $heldByOther = SeatHold::where('bus_id', $bus->id)
            ->where('expires_at', '>', now())
            ->where('session_id', '!=', $sessionId)
            ->whereIn('seat_name', $ticketlist)
            ->pluck('seat_name')
            ->all();

        if (!empty($heldByOther)) {
            return redirect()->route('seat_view', $bus->id)
                ->with('error', 'Seat ' . implode(', ', $heldByOther) . ' is temporarily on hold by another customer. Please choose different seats.');
        }

        // Release any previous holds created by this session
        SeatHold::where('session_id', $sessionId)->delete();

        // Hold selected seats for 10 minutes
        $expiresAt = now()->addMinutes(10);
        try {
            DB::transaction(function () use ($bus, $ticketlist, $sessionId, $expiresAt) {
                foreach ($ticketlist as $seat) {
                    SeatHold::create([
                        'bus_id'     => $bus->id,
                        'seat_name'  => $seat,
                        'session_id' => $sessionId,
                        'user_id'    => auth()->id(),
                        'expires_at' => $expiresAt,
                    ]);
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('seat_view', $bus->id)
                ->with('error', 'One or more of your selected seats were just put on hold by another customer. Please choose available seats.');
        }

        return view('exampleHosted', compact('ticketlist', 'bus', 'expiresAt'));
    }


    public function seat_management(Request $request)
    {
        $id = $request->input('id');

        // Retrieve the bus record from the database
        $bus = Bus::find($id);
        $view = $bus->view;

        if (!$bus) {

            return redirect()->back()->with('error', 'Bus not found.');
        }
        $newview = $view;
        $checkboxNames = [];
        for ($i = 'A'; $i <= 'J'; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                $checkboxNames[] = $i . $j;
            }
        }
        $ticketlist = [];
        if (auth()->check()) {

            for ($i = 0; $i < count($checkboxNames); $i++) {
                if ($request->input($checkboxNames[$i]) != null) {
                    $ticketlist[] = $checkboxNames[$i];
                    $newview[$i] = $request->input($checkboxNames[$i]);
                }
            }
        }
        if (!count($ticketlist)) {
            return redirect()->back()->with('error', 'Please select at least a seat and login!!');
        }

        // for ($i = 0; $i < 8; $i++) {
        //     if ($newview[$i] == '2') {
        //         $newview[$i] = $view[$i];
        //     }
        // }
        $bus->view = $newview;
        $seats_available = 0;

        for ($i = 0; $i < strlen($newview); $i++) {
            if ($newview[$i] == '0') {
                $seats_available++;
            }
        }
        $bus->seats_available = $seats_available;
        $bus->save();
        // $test = 3;

        return view('showdownloadinfo', compact('bus', 'ticketlist'));
    }
    public function showdownloadinfo(Request $request)
    {
        $order = $this->accessibleOrder($request);
        $bus = Bus::findOrFail($order->bus_id);
        $ticketlist = json_decode($order->ticketlist, true);
        $card_issuer = $order->card_issuer;
        return view('showdownloadinfo', compact('bus', 'ticketlist', 'order', 'card_issuer'));
    }

    public function downloadTicket(Request $request)
    {
        $order = $this->accessibleOrder($request);
        $ticketlist = json_decode($order->ticketlist, true);
        abort_unless(is_array($ticketlist) && $ticketlist !== [], 422, 'This order has no ticket seats.');

        $bus = Bus::findOrFail($order->bus_id);
        $pdf = Pdf::loadView('downloadinfo', compact('bus', 'ticketlist', 'order'));
        return $pdf->download('JatraPoth-ticket-' . $order->transaction_id . '.pdf');
    }

    private function accessibleOrder(Request $request): Order
    {
        $orderId = $request->integer('order_id');
        abort_if($orderId <= 0, 404);
        $order = Order::findOrFail($orderId);
        $providedToken = (string) $request->input('token');
        $hasValidToken = $providedToken !== ''
            && hash_equals($order->downloadToken(), $providedToken);
        $ownsOrder = auth()->check()
            && strcasecmp((string) auth()->user()->email, (string) $order->email) === 0;

        abort_unless($hasValidToken || $ownsOrder, 403);

        return $order;
    }

    public function seat_view($id)
    {
        // Retrieve the bus details based on the coach number
        $bus = Bus::find($id);
        if ($bus) {
            // Clean up globally expired holds
            SeatHold::cleanupExpired();

            // When returning to seat selection, release any existing holds for this session
            SeatHold::where('session_id', session()->getId())->delete();

            // Get active held seats for this bus
            $heldSeats = SeatHold::where('bus_id', $bus->id)
                ->where('expires_at', '>', now())
                ->pluck('seat_name')
                ->all();

            return view('seat_view', compact('bus', 'heldSeats'));
        }

        return view('check', compact('id', 'ticketlist'));
    }

    public function bus_reviews($id)
    {
        $bus = Bus::findOrFail($id);

        // Get statistics
        $totalReviews = $bus->seatRatings()->count();
        $averageRating = $bus->seatRatings()->avg('rating') ?? 0;
        $ratedSeats = $bus->seatRatings()->distinct('seat_name')->count();

        // Get seat statistics
        $seatStats = $bus->getSeatRatingStats();

        // Get recent reviews
        $recentReviews = $bus->getRecentReviews(20);

        return view('bus_reviews', compact('bus', 'totalReviews', 'averageRating', 'ratedSeats', 'seatStats', 'recentReviews'));
    }
}
