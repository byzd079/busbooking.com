<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Admin;
use App\Models\Bus;
use App\Models\buslist;
use App\Models\Order;
use App\Models\User;

class AdminController extends Controller
{
    public function adminRegisterPost()
    {
        $admin = new Admin();
        $admin->email = 'admin283@gmail.com';
        $password = '92689268';
        $admin->password = Hash::make($password);
        $admin->save();
        Session::flash('success', 'Registration Successful');
    }

    public function adminLogin(){
        return view('admin.login');
    }

    public function adminLoginPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('admin')->attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function admin_dashboard()
    {
        $totalBuses = Bus::count();
        $totalUsers = User::count();
        $totalPurchasedTickets = Order::where('status', 'Processing')->orWhere('status', 'Successful')->count();
        $totalPendingTickets = Order::where('status', 'Pending')->count();
        $totalCanceledTickets = Order::where('status', 'Failed')->orWhere('status', 'Canceled')->count();
        $totalRefundRequests = Order::where('status', 'Refunding')->count();
        $totalRevenue = Order::where('status', 'Processing')->orWhere('status', 'Successful')->sum('amount');

        return view('admin.dashboard', compact(
            'totalBuses',
            'totalUsers',
            'totalPurchasedTickets',
            'totalPendingTickets',
            'totalCanceledTickets',
            'totalRefundRequests',
            'totalRevenue'
        ));
    }

    public function seat_info()
    {
        return view('admin.seat_info');
    }

    public function fetchBusData(Request $request)
    {
        $bus_date = $request->input('bus_date');
        IfNotFoundThenCreate($bus_date);
        $bus = Bus::where('date', $bus_date)->get();
        return view('admin.seat_info_view', compact('bus'));
    }

    public function admin_seat_view($id)
    {
        $bus = Bus::find($id);
        return view('admin.seat_viewAdmin', compact('bus'));
    }

    public function admin_seat_info_button(Request $request)
    {
        return view('admin.seat_info');
    }

    public function showuser()
    {
        return view('admin.showuser');
    }

    public function admin_search(Request $request)
    {
        $query = $request->input('query');
        $users = [];

        if ($query) {
            $users = User::where('mobile_no', 'like', '%' . $query . '%')
                ->orWhere('email', 'like', '%' . $query . '%')
                ->orWhere('name', 'like', '%' . $query . '%')
                ->get();
        }

        return view('admin.show_user_search', compact('users', 'query'));
    }

    public function adminLogOut()
    {
        Auth::guard('admin')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('home')->with('success', 'Logged out successfully');
    }

    public function updateSeatLayout(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'seat_layout' => 'required|string',
        ]);

        $bus = Bus::findOrFail($request->bus_id);
        $newLayout = $request->seat_layout;
        $availableSeats = substr_count($newLayout, '0');

        $bus->view = $newLayout;
        $bus->seats_available = $availableSeats;
        $bus->save();

        return response()->json([
            'success' => true,
            'message' => 'Seat layout updated successfully',
            'available_seats' => $availableSeats
        ]);
    }

    public function adminOrders(Request $request)
    {
        $order = Order::all();
        return view('admin.orders', compact('order'));
    }

    public function adminOrderSearch(Request $request)
    {
        $query = $request->input('query');
        $order = [];

        if ($query) {
            $order = Order::where('transaction_id', 'like', '%' . $query . '%')
                ->orWhere('name', 'like', '%' . $query . '%')
                ->orWhere('email', 'like', '%' . $query . '%')
                ->orWhere('phone', 'like', '%' . $query . '%')
                ->orWhere('amount', 'like', '%' . $query . '%')
                ->orWhere('status', 'like', '%' . $query . '%')
                ->orWhere('card_issuer', 'like', '%' . $query . '%')
                ->orWhere('currency', 'like', '%' . $query . '%')
                ->get();
        }

        return view('admin.orders', compact('order', 'query'));
    }

    /**
     * Show Bulk Bus Generator Form
     */
    public function showBulkGenerator()
    {
        $masterBuses = buslist::all();
        return view('admin.generate_buses', compact('masterBuses'));
    }

    /**
     * Process Bulk Bus Generation across Date Ranges
     */
    public function processBulkGenerator(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = new \DateTime($request->start_date);
        $endDate = new \DateTime($request->end_date);
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

        $masterBuses = buslist::all();
        if ($masterBuses->isEmpty()) {
            return redirect()->back()->with('error', 'No master bus templates found in buslist table.');
        }

        $generatedCount = 0;
        foreach ($dateRange as $dateObj) {
            $dateStr = $dateObj->format('Y-m-d');

            foreach ($masterBuses as $template) {
                // Check if bus already exists for this coach and date
                $exists = Bus::where('coach_no', $template->coach_no)
                    ->where('date', $dateStr)
                    ->first();

                if (!$exists) {
                    Bus::create([
                        'date' => $dateStr,
                        'bus_name' => $template->bus_name,
                        'departing_time' => $template->departing_time,
                        'coach_no' => $template->coach_no,
                        'starting_point' => $template->starting_point,
                        'ending_point' => $template->ending_point,
                        'fare' => $template->fare,
                        'coach_type' => $template->coach_type,
                        'seats_available' => $template->seats_available ?? 40,
                        'view' => $template->view ?? str_repeat('0', 40),
                        'total_seats' => $template->total_seats ?? 40,
                    ]);
                    $generatedCount++;
                }
            }
        }

        return redirect()->route('admin.dashboard')->with('success', "Bulk Bus Generator successfully created {$generatedCount} new bus schedules across selected dates!");
    }
}
