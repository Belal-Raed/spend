<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DebtRepaymentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\EnsureAccountIsWritable;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    Route::get('/settings', SettingsController::class)->name('settings');
    Route::get('/export', ExportController::class)->name('export');

    Route::get('/subscription', SubscriptionController::class)->name('subscription');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Everything that adds, changes or deletes records stops once the trial or subscription ends.
    Route::middleware(EnsureAccountIsWritable::class)->group(function () {
        Route::resource('transactions', TransactionController::class)->except(['index', 'show']);

        Route::get('/debts/{debt}/repayments/create', [DebtRepaymentController::class, 'create'])->name('debts.repayments.create');
        Route::post('/debts/{debt}/repayments', [DebtRepaymentController::class, 'store'])->name('debts.repayments.store');

        Route::resource('categories', CategoryController::class)->except(['index', 'show', 'create']);
    });
});
