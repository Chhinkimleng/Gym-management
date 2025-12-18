<?php
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\PaymentController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

auth::routes();


Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Members Management
    Route::resource('members', MemberController::class);
    
    // Membership Plans Management
    Route::resource('membership-plans', MembershipPlanController::class);
    
    



    
    // Subscriptions Management
    Route::get('subscriptions/create/{member}', [SubscriptionController::class, 'create'])
        ->name('subscriptions.create');
    Route::post('subscriptions', [SubscriptionController::class, 'store'])
        ->name('subscriptions.store');
    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])
        ->name('subscriptions.show');
    Route::patch('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])
        ->name('subscriptions.cancel');
    
    
    // Payments Management
    Route::resource('payments', PaymentController::class);
    Route::get('members/{member}/subscriptions', [PaymentController::class, 'getSubscriptions'])
        ->name('members.subscriptions');
    // Create payment for a specific member
    Route::get('members/{member}/payments/create', [PaymentController::class, 'createForMember'])
        ->name('members.payments.create');


    //payment pdf
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

    
    // Attendance Management
    Route::get('attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->name('attendance.checkin');
    Route::patch('attendance/{attendance}/check-out', [AttendanceController::class, 'checkOut'])
        ->name('attendance.checkout');
    Route::get('attendance/report', [AttendanceController::class, 'report'])
        ->name('attendance.report');
    Route::get('members/{member}/attendance', [AttendanceController::class, 'memberAttendance'])
        ->name('members.attendance');
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->name('attendance.checkin');
    Route::get('attendance/report', [AttendanceController::class, 'report'])
        ->name('attendance.report');



});