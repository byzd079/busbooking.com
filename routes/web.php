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
// Diagnostic routes removed before launch (N5):
// /extra, /temporary, /layout, /master2, /example1, /test-bus-rating/{id}
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
// SECURITY: these routes create/edit/delete buses and expose the admin bus panel.
// They previously sat outside every middleware group, so anyone could call
// DELETE /bus/{id} or POST /storedata unauthenticated. Guarded with the same
// 'admin' middleware the rest of the admin panel already uses.
Route::middleware(['admin'])->group(function () {
    Route::get('/createdata', [BusController::class, 'createdata'])->name('createdata.view');
    Route::post('/storedata', [BusController::class, 'storedata'])->name('createdata.store');
    Route::get('/editdata/{id}', [BusController::class, 'edit']);
    Route::post('/updatedata/{id}', [BusController::class, 'update']);
    Route::put('/bus/{bus}', [BusController::class, 'update'])->name('bus.update');
    Route::delete('/bus/{bus}', [BusController::class, 'destroy'])->name('bus.destroy');
    Route::get('/showdata', [BusController::class, 'showdata'])->name('showdata'); // buslist will be shown from admin panel
    Route::get('/seat_info', [AdminController::class, 'seat_info'])->name('seat_info.view');
});


//user pannel
Route::post('/register', [AuthController::class, 'register'])->name("register");
Route::post('/log_in', [AuthController::class, 'log_in'])->name('log_in');
Route::get('/log_out', [AuthController::class, 'log_out'])->name('log_out');
Route::get('/view_profile', [AuthController::class, 'view_profile'])->name('view_profile');
Route::get('/edit_profile', [AuthController::class, 'edit_profile'])->name('edit_profile');
Route::post('/update_profile', [AuthController::class, 'update_profile'])->name('update_profile');

// Diagnostic routes removed (N5): /layout, /temporary

Route::get('change_password', [AuthController::class, 'change_password'])->name('change_password')->middleware('onlyuser');
Route::post('update_password', [AuthController::class, 'update_password'])->name('update_password')->middleware('onlyuser');
Route::get('/search_bus', [SearchController::class, 'search_bus'])->name('search_bus');
Route::get('seat_management', [SearchController::class, 'seat_management'])->name('seat_management')->middleware('notguest');
Route::get('/seat_view/{id}', [SearchController::class, 'seat_view'])->name('seat_view');
// Route::get('/showbustable', [YourControllerName::class, 'show_bus'])->name('show_bus');
// Route::post('/showbustable', [SearchController::class, 'search_bus'])->name('search_bus');

//forgot password
Route::get('/forgot_password', [ForgotPasswordManager::class, 'forgot_password'])->name('forgot_password.view')->middleware('onlyguest');
Route::post('/forgot_password', [ForgotPasswordManager::class, 'forgot_passwordPost'])->name('forgot_passwordPost')->middleware('onlyguest');
Route::get('/resetPassword/{token}', [ForgotPasswordManager::class, 'resetPassword'])->name('resetPassword')->middleware('onlyguest');
Route::post('/resetPassword', [ForgotPasswordManager::class, 'resetPasswordPost'])->name('resetPasswordPost');



// payment gateway
// SSLCOMMERZ Start
// Diagnostic routes removed (N5): /example1
Route::get('/payment_details', [SearchController::class, 'payment_details'])->name('payment_details');
route::get('/showdownloadinfo', [SearchController::class, 'showdownloadinfo'])->name('showdownloadinfo');
Route::post('/pay', [SslCommerzPaymentController::class, 'index']);
Route::get('/downloadTicket', [SearchController::class, 'downloadTicket'])->name('downloadTicket');
// Removed: /pay-via-ajax - method doesn't exist in controller

Route::match(['get', 'post'], '/success', [SslCommerzPaymentController::class, 'success'])->name('payment.success');
Route::post('/fail', [SslCommerzPaymentController::class, 'fail'])->name('payment.fail');
Route::get('/cancel', [SslCommerzPaymentController::class, 'cancel'])->name('payment.cancel');
Route::post('/ipn', [SslCommerzPaymentController::class, 'ipn'])->name('payment.ipn');


// Diagnostic routes removed (N5): /master2


// REMOVED: AdminRegisterPost - hardcoded credentials, use `php artisan admin:create` instead

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

// Diagnostic routes removed (N5): /test-bus-rating/{id}

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


// ---------------------------------------------------------------------------
// Bus Community: photo gallery, comment threads, combined trip rating
// ---------------------------------------------------------------------------

// Public: anyone can browse a bus gallery and read the conduct score.
Route::get('/bus/{busId}/gallery', [\App\Http\Controllers\BusPostController::class, 'index'])->name('bus.gallery');
Route::get('/bus-post/{postId}/image', [\App\Http\Controllers\BusPostController::class, 'image'])->name('bus.post.image');
Route::get('/bus-post/{postId}/comments', [\App\Http\Controllers\PostCommentController::class, 'index'])->name('post.comments');
Route::get('/bus/{busId}/behavior-summary', [\App\Http\Controllers\TripRatingController::class, 'summary'])->name('bus.behavior.summary');

// Signed-in: posting, replying, marking helpful, reporting.
Route::middleware(['auth'])->group(function () {
    Route::post('/bus-post', [\App\Http\Controllers\BusPostController::class, 'store'])->name('bus.post.store');
    Route::post('/bus-post/{postId}/helpful', [\App\Http\Controllers\BusPostController::class, 'toggleHelpful'])->name('bus.post.helpful');
    Route::post('/bus-post/{postId}/flag', [\App\Http\Controllers\BusPostController::class, 'flag'])->name('bus.post.flag');
    Route::delete('/bus-post/{postId}', [\App\Http\Controllers\BusPostController::class, 'destroy'])->name('bus.post.destroy');

    Route::post('/bus-post/{postId}/comment', [\App\Http\Controllers\PostCommentController::class, 'store'])->name('post.comment.store');
    Route::put('/comment/{commentId}', [\App\Http\Controllers\PostCommentController::class, 'update'])->name('post.comment.update');
    Route::delete('/comment/{commentId}', [\App\Http\Controllers\PostCommentController::class, 'destroy'])->name('post.comment.destroy');
    Route::post('/comment/{commentId}/flag', [\App\Http\Controllers\PostCommentController::class, 'flag'])->name('post.comment.flag');

    // Combined post-trip rating: seat stars + five conduct dimensions in one form.
    Route::get('/rate-my-trip/{orderId}', [\App\Http\Controllers\TripRatingController::class, 'showForm'])->name('trip.rating.form');
    Route::post('/rate-my-trip', [\App\Http\Controllers\TripRatingController::class, 'store'])->name('trip.rating.store');

    // "My contributions" panel
    Route::get('/my-contributions', [\App\Http\Controllers\UserContributionController::class, 'index'])->name('my.contributions');
});


// Official Seat Swapping System Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/seat-swap/request/{orderId}', [\App\Http\Controllers\SeatSwapController::class, 'showSwapForm'])->name('seat.swap.form');
    Route::post('/seat-swap/request', [\App\Http\Controllers\SeatSwapController::class, 'requestSwap'])->name('seat.swap.request');
    Route::get('/seat-swap/requests', [\App\Http\Controllers\SeatSwapController::class, 'mySwapRequests'])->name('seat.swap.list');
    Route::post('/seat-swap/accept/{id}', [\App\Http\Controllers\SeatSwapController::class, 'acceptSwap'])->name('seat.swap.accept');
    Route::post('/seat-swap/decline/{id}', [\App\Http\Controllers\SeatSwapController::class, 'declineSwap'])->name('seat.swap.decline');
    Route::post('/seat-swap/cancel/{id}', [\App\Http\Controllers\SeatSwapController::class, 'cancelSwap'])->name('seat.swap.cancel');
});


