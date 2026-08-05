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
        // Retrieve all form data
        $date = $request->input('date');
        $starting_point = $request->input('starting_point');
        $ending_point = $request->input('ending_point');
        if (empty($date) && empty($starting_point) && empty($ending_point)) {
            $today  = Carbon::today()->toDateString();
            $buses  = bus::where('date', $today)->get();
            
            // Add bus ratings to each bus
            foreach ($buses as $bus) {
                $bus->rating_data = $bus->getRatingSummary();
            }
            
            // Sort buses by rating (highest first)
            $buses = $buses->sortByDesc(function ($bus) {
                return $bus->rating_data['average_rating'];
            })->values();

            $this->attachCommunityData($buses);

            return view('showbustable', compact('buses'));
        }
        // $departureDate = $request->input('depart-date');
        // $returnDate = $request->input('return-date');

        // $bus = bus::where('date', $date)->get();
        // if ($bus != '[]') {
        //     $buses = bus::where('date', $date)
        //         ->where('starting_point', $starting_point)
        //         ->where('ending_point', $ending_point)->get();
        //     if ($buses != '[]') {
        //         return view('showbustable', compact('buses'));
        //     }
        //     Session::flash('msg', 'No Bus Found In This Route');

        //     return view('showbustable', compact('buses'));
        // } else {
        //     $bus = buslist::all();
        //     foreach ($bus as $key => $value) {
        //         $newbus = new bus();
        //         $newbus->date = $date;
        //         $newbus->bus_name = $value->bus_name;
        //         $newbus->departing_time = $value->departing_time;
        //         $newbus->coach_no = $value->coach_no;
        //         $newbus->starting_point = $value->starting_point;
        //         $newbus->ending_point = $value->ending_point;
        //         $newbus->fare = $value->fare;
        //         $newbus->coach_type = $value->coach_type;
        //         $newbus->seats_available = $value->seats_available;
        //         $newbus->view = $value->view;
        //         $newbus->save();
        //     }
        // dd($request->all());
        IfNotFoundThenCreate($date);
        $buses = bus::where('date', $date)
            ->where('starting_point', $starting_point)
            ->where('ending_point', $ending_point)->get();
        
        // Add bus ratings to each bus
        foreach ($buses as $bus) {
            $bus->rating_data = $bus->getRatingSummary();
        }
        
        // Sort buses by rating (highest first)
        $buses = $buses->sortByDesc(function ($bus) {
            return $bus->rating_data['average_rating'];
        })->values();

        $this->attachCommunityData($buses);

        // dd($buses);
        if ($buses != '[]') {
            return view('showbustable', compact('buses'));
        }
        Session::flash('msg', 'No Bus Found In This Route');

        return view('showbustable', compact('buses'));
        // }
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
        return view('exampleHosted', compact('ticketlist', 'bus'));
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
            return view('seat_view', compact('bus'));
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
