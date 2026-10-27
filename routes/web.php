<?php

use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\AccountProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\TopUpRequestController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TicketController::class, 'index'])
    ->middleware('track.activity')
    ->name('home');
Route::get('/tickets/{ticket}/stock', [TicketController::class, 'stock'])
    ->middleware('throttle:120,1')
    ->name('tickets.stock');
Route::get('/tickets/{ticket}/registrations', [TicketController::class, 'registrations'])
    ->middleware('throttle:30,1')
    ->name('tickets.registrations');
Route::post('/midtrans/notification', MidtransNotificationController::class)
    ->middleware('throttle:60,1')
    ->name('payments.midtrans.notification');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
});

Route::middleware(['auth', 'track.activity'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account/profile', [AccountProfileController::class, 'edit'])->name('account.profile.edit');
    Route::put('/account/profile', [AccountProfileController::class, 'update'])->name('account.profile.update');
    Route::get('/account/password', [AccountPasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('/account/password', [AccountPasswordController::class, 'update'])->name('account.password.update');
    Route::get('/account/wallet', [WalletController::class, 'index'])->name('account.wallet');
    Route::post('/account/wallet/top-up-requests', [WalletController::class, 'requestTopUp'])
        ->middleware('throttle:10,1')
        ->name('account.wallet.top-ups.store');
    Route::get('/tickets/{ticket}/captcha-image', [TicketController::class, 'generateCaptcha'])
        ->middleware('throttle:60,1')
        ->name('captcha.image');
    Route::post('/tickets/{ticket}/buy', [TicketController::class, 'buy'])
        ->middleware('throttle:10,1')
        ->block(10, 10)
        ->name('tickets.buy');
    Route::get('/orders/{order}/payment', [TicketController::class, 'payment'])
        ->name('orders.payment');
    Route::post('/orders/{order}/payment/wallet', [TicketController::class, 'payWithWallet'])
        ->block(10, 10)
        ->name('orders.payment.wallet');
    Route::post('/orders/{order}/payment/midtrans', [TicketController::class, 'payWithMidtrans'])
        ->middleware('throttle:5,1')
        ->block(10, 10)
        ->name('orders.payment.midtrans');
});

Route::middleware(['auth', 'track.activity', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/top-ups', [TopUpRequestController::class, 'index'])->name('top-ups.index');
    Route::post('/top-ups/{topUpRequest}/approve', [TopUpRequestController::class, 'approve'])->name('top-ups.approve');
    Route::post('/top-ups/{topUpRequest}/reject', [TopUpRequestController::class, 'reject'])->name('top-ups.reject');
    Route::resource('tickets', AdminTicketController::class)->except('show');
});
