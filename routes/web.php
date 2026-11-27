<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['km', 'en'], true)) {
        session(['locale' => $locale]);
    }
    return back();
})->name('locale.switch');

Auth::routes(['register' => false]);
Route::post('/demo-login', [\App\Http\Controllers\Auth\LoginController::class, 'demoLogin'])
    ->middleware(['guest', 'throttle:20,1'])
    ->name('demo.login');

Route::middleware(['auth', 'active.user'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Members Management
    Route::patch('members/{member}/deactivate', [MemberController::class, 'deactivate'])
        ->name('members.deactivate');
    Route::resource('members', MemberController::class)->except(['destroy']);
    Route::delete('members/{member}', [MemberController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('members.destroy');

    // Membership Plans Management (View for all staff; create/edit/toggle/delete for Admin)
    Route::get('membership-plans', [MembershipPlanController::class, 'index'])
        ->name('membership-plans.index');
    Route::middleware('role:admin')->group(function () {
        Route::get('membership-plans/create', [MembershipPlanController::class, 'create'])
            ->name('membership-plans.create');
        Route::post('membership-plans', [MembershipPlanController::class, 'store'])
            ->name('membership-plans.store');
        Route::get('membership-plans/{membership_plan}/edit', [MembershipPlanController::class, 'edit'])
            ->name('membership-plans.edit');
        Route::put('membership-plans/{membership_plan}', [MembershipPlanController::class, 'update'])
            ->name('membership-plans.update');
        Route::patch('membership-plans/{membership_plan}/toggle', [MembershipPlanController::class, 'toggleStatus'])
            ->name('membership-plans.toggle');
        Route::delete('membership-plans/{membership_plan}', [MembershipPlanController::class, 'destroy'])
            ->name('membership-plans.destroy');
    });

    // Memberships / Subscriptions Management
    Route::get('memberships', [SubscriptionController::class, 'index'])
        ->name('memberships.index');
    Route::get('subscriptions/create/{member?}', [SubscriptionController::class, 'create'])
        ->name('subscriptions.create');
    Route::post('subscriptions', [SubscriptionController::class, 'store'])
        ->name('subscriptions.store');
    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])
        ->name('subscriptions.show');
    Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])
        ->name('subscriptions.renew');
    Route::patch('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])
        ->name('subscriptions.cancel');

    // Payments Management
    Route::resource('payments', PaymentController::class)->except(['destroy']);
    Route::post('payments/{payment}/installment', [PaymentController::class, 'recordInstallment'])
        ->name('payments.installment');
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])
        ->middleware('role:admin')
        ->name('payments.refund');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('payments.destroy');
    Route::get('members/{member}/subscriptions', [PaymentController::class, 'getSubscriptions'])
        ->name('members.subscriptions');
    Route::get('members/{member}/payments/create', [PaymentController::class, 'createForMember'])
        ->name('members.payments.create');
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])
        ->name('payments.receipt');

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

    // Reports & Analytics
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

    // Settings & Administration
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::middleware('role:admin')->group(function () {
        Route::put('settings/gym', [SettingsController::class, 'updateGymSettings'])->name('settings.gym.update');
        Route::post('settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::patch('settings/users/{user}/toggle', [SettingsController::class, 'toggleUserStatus'])->name('settings.users.toggle');
        Route::patch('settings/users/{user}/role', [SettingsController::class, 'updateUserRole'])->name('settings.users.role');
        Route::get('settings/backup', [SettingsController::class, 'downloadBackup'])->name('settings.backup');
    });
});
