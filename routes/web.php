<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\ForgotPasswordManager;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SslCommerzPaymentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

Route::get("/", function () {
    return view("homeview");
})->name("home");
Route::get("/extra", function () {
    return view("createdata");
})->name("extra");
Route::get("/about", function () {
    return view("aboutview");
})->name("about");
Route::get("/login", function () {
    return view("loginview");
})->name("login")->middleware('onlyguest');
Route::get("/buy", function () {
    return view("buyview");
})->name("buy");


//admin pannel
// Route::get('/showdata', [BusController::class, 'showdata']);
Route::get('/createdata', [BusController::class, 'createdata'])->name('createdata.view');
Route::post('/storedata', [BusController::class, 'storedata'])->name('createdata.store');
Route::get('/editdata/{id}', [BusController::class, 'edit']);
Route::post('/updatedata/{id}', [BusController::class, 'update']);
Route::put('/bus/{bus}', [BusController::class, 'update'])->name('bus.update');
Route::delete('/bus/{bus}', [BusController::class, 'destroy'])->name('bus.destroy');
Route::get('/showdata', [BusController::class, 'showdata'])->name('showdata'); // buslist will be shown from admin panel
route::get('/seat_info', [AdminController::class, 'seat_info'])->name('seat_info.view');


//user pannel
Route::post('/register', [AuthController::class, 'register'])->name("register");
Route::post('/log_in', [AuthController::class, 'log_in'])->name('log_in');
Route::get('/log_out', [AuthController::class, 'log_out'])->name('log_out');
Route::get('/view_profile', [AuthController::class, 'view_profile'])->name('view_profile');
Route::get('/edit_profile', [AuthController::class, 'edit_profile'])->name('edit_profile');
Route::post('/update_profile', [AuthController::class, 'update_profile'])->name('update_profile');

Route::get('/layout', function () {
    return view('layout');
});
// web.php or routes/web.php

Route::get('change_password', [AuthController::class, 'change_password'])->name('change_password')->middleware('onlyuser');
Route::post('update_password', [AuthController::class, 'update_password'])->name('update_password')->middleware('onlyuser');
Route::get('/search_bus', [SearchController::class, 'search_bus'])->name('search_bus');
Route::get('seat_management', [SearchController::class, 'seat_management'])->name('seat_management')->middleware('notguest');
Route::get('/seat_view/{id}', [SearchController::class, 'seat_view'])->name('seat_view');
Route::get('/temporary', [BusController::class, 'temporary'])->name('temporary'); //just for checking
// Route::get('/showbustable', [YourControllerName::class, 'show_bus'])->name('show_bus');
// Route::post('/showbustable', [SearchController::class, 'search_bus'])->name('search_bus');

//forgot password
Route::get('/forgot_password', [ForgotPasswordManager::class, 'forgot_password'])->name('forgot_password.view')->middleware('onlyguest');
Route::post('/forgot_password', [ForgotPasswordManager::class, 'forgot_passwordPost'])->name('forgot_passwordPost')->middleware('onlyguest');
Route::get('/resetPassword/{token}', [ForgotPasswordManager::class, 'resetPassword'])->name('resetPassword')->middleware('onlyguest');
Route::post('/resetPassword', [ForgotPasswordManager::class, 'resetPasswordPost'])->name('resetPasswordPost');



// payment gateway
// SSLCOMMERZ Start
Route::get('/example1', [SslCommerzPaymentController::class, 'exampleEasyCheckout']);
// Route::get('/example2', [SslCommerzPaymentController::class, 'exampleHostedCheckout']);
Route::get('/payment_details', [SearchController::class, 'payment_details'])->name('payment_details');
route::get('/showdownloadinfo', [SearchController::class, 'showdownloadinfo'])->name('showdownloadinfo');
Route::post('/pay', [SslCommerzPaymentController::class, 'index']);
Route::get('/downloadTicket', [SearchController::class, 'downloadTicket'])->name('downloadTicket');
// Removed: /pay-via-ajax - method doesn't exist in controller

Route::match(['get', 'post'], '/success', [SslCommerzPaymentController::class, 'success'])->name('payment.success');
Route::post('/fail', [SslCommerzPaymentController::class, 'fail'])->name('payment.fail');
Route::get('/cancel', [SslCommerzPaymentController::class, 'cancel'])->name('payment.cancel');
Route::post('/ipn', [SslCommerzPaymentController::class, 'ipn'])->name('payment.ipn');


Route::get('/master2', function () {
    return view('layout.navbar');
});


// this function will only available for backend developer
// route::get('/AdminRegisterPost', [AdminController::class, 'AdminRegisterPost'])->name('AdminRegisterPost');

route::get('/purchase_history', [AuthController::class, 'purchase_history'])->name('purchase_history');

// REMOVED: Public admin self-registration (security vulnerability)
// Route::get('/custom_register', [CustomController::class, 'custom_register'])->name('custom_register');
// Route::post('/custom_register', [CustomController::class, 'custom_registerPost'])->name('custom_registerPost');

// Admin Login (public route)
route::get('/admin_login', [AdminController::class, 'adminLogin'])->name('admin_login.view');
Route::post('/admin_login', [AdminController::class, 'adminLoginPost'])
    ->middleware('throttle:5,1')
    ->name('admin_login.post');

// Admin Routes (protected)
Route::middleware(['admin'])->group(function () {
    route::get('/admin.dashboard', [AdminController::class, 'admin_dashboard'])->name('admin.dashboard');
    Route::post('/fetch_bus_data', [AdminController::class, 'fetchBusData'])->name('fetch_bus_data');
    // seat_info
    route::get('/admin_seat_info_button', [AdminController::class, 'admin_seat_info_button'])->name('admin_seat_info_button');
    Route::get('/admin_seat_view/{id}', [AdminController::class, 'admin_seat_view'])->name('admin_seat_view');
    // admin.showuser route
    Route::get('/showuser', [AdminController::class, 'showuser'])->name('admin_show_all_user');
    Route::get('/admin_search', [AdminController::class, 'admin_search'])->name('admin_search');
    //adminLogOut
    Route::get('/adminLogOut', [AdminController::class, 'adminLogOut'])->name('adminLogOut');
    //adminOrders
    Route::get('/adminOrders', [AdminController::class, 'adminOrders'])->name('adminOrders');
    //adminOrderSearch
    Route::get('/adminOrderSearch', [AdminController::class, 'adminOrderSearch'])->name('adminOrderSearch');
    // Seat layout management
    Route::post('/admin/update-seat-layout', [AdminController::class, 'updateSeatLayout'])->name('updateSeatLayout');
    Route::get('/admin/generate-buses', [AdminController::class, 'showBulkGenerator'])->name('admin.generate.view');
    Route::post('/admin/generate-buses', [AdminController::class, 'processBulkGenerator'])->name('admin.generate.process');
});

// REMOVED: Duplicate admin login route (security vulnerability)
// Route::post('/custom_login', [CustomController::class, 'custom_loginPost'])->name('custom_loginPost');

// Seat Rating System Routes
Route::get('/seat-ratings-table', [\App\Http\Controllers\SeatRatingController::class, 'showSeatRatingsTable'])->name('seat.ratings.table');
Route::get('/bus-reviews', [\App\Http\Controllers\SeatRatingController::class, 'getBusReviews'])->name('bus.reviews');

Route::middleware(['auth'])->group(function () {
    Route::get('/rate-trip/{busId}', [\App\Http\Controllers\SeatRatingController::class, 'showRatingForm'])->name('rate.trip.form');
    Route::post('/seat-rating', [\App\Http\Controllers\SeatRatingController::class, 'storeTest'])->name('seat.rating.store');
    Route::get('/seat-reviews', [\App\Http\Controllers\SeatRatingController::class, 'showSeatReviews'])->name('seat.reviews');
    Route::put('/seat-rating/{id}', [\App\Http\Controllers\SeatRatingController::class, 'update'])->name('seat.rating.update');
    Route::delete('/seat-rating/{id}', [\App\Http\Controllers\SeatRatingController::class, 'destroy'])->name('seat.rating.destroy');
    Route::get('/check-user-rating', [\App\Http\Controllers\SeatRatingController::class, 'checkUserRating'])->name('check.user.rating');
});

// Bus API Routes
Route::get('/api/bus/{id}', function ($id) {
    $bus = App\Models\Bus::find($id);
    if ($bus) {
        return response()->json([
            'success' => true,
            'bus' => $bus
        ]);
    }
    return response()->json([
        'success' => false,
        'message' => 'Bus not found'
    ], 404);
})->name('api.bus.show');

// Test route for bus ratings
Route::get('/test-bus-rating/{id}', function ($id) {
    $bus = App\Models\Bus::find($id);
    if ($bus) {
        $ratingSummary = $bus->getRatingSummary();
        return response()->json([
            'success' => true,
            'bus' => $bus,
            'rating_summary' => $ratingSummary
        ]);
    }
    return response()->json([
        'success' => false,
        'message' => 'Bus not found'
    ], 404);
})->name('test.bus.rating');

// Refund System Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/refund/policy/{orderId}', [\App\Http\Controllers\RefundController::class, 'showRefundPolicy'])->name('refund.policy');
    Route::post('/refund/process/{orderId}', [\App\Http\Controllers\RefundController::class, 'processRefund'])->name('refund.process');
});

// Admin Refund Routes
Route::middleware(['admin'])->prefix('admin')->group(function () {
    Route::get('/refund-requests', [\App\Http\Controllers\RefundController::class, 'adminRefundRequests'])->name('admin.refund.requests');
    Route::post('/refund/confirm/{orderId}', [\App\Http\Controllers\RefundController::class, 'confirmRefund'])->name('admin.refund.confirm');
});


//SSLCOMMERZ END


// Official Seat Swapping System Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/seat-swap/request/{orderId}', [\App\Http\Controllers\SeatSwapController::class, 'showSwapForm'])->name('seat.swap.form');
    Route::post('/seat-swap/request', [\App\Http\Controllers\SeatSwapController::class, 'requestSwap'])->name('seat.swap.request');
    Route::get('/seat-swap/requests', [\App\Http\Controllers\SeatSwapController::class, 'mySwapRequests'])->name('seat.swap.list');
    Route::post('/seat-swap/accept/{id}', [\App\Http\Controllers\SeatSwapController::class, 'acceptSwap'])->name('seat.swap.accept');
    Route::post('/seat-swap/decline/{id}', [\App\Http\Controllers\SeatSwapController::class, 'declineSwap'])->name('seat.swap.decline');
    Route::post('/seat-swap/cancel/{id}', [\App\Http\Controllers\SeatSwapController::class, 'cancelSwap'])->name('seat.swap.cancel');
});


