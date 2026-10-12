<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DebtRepaymentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::resource('transactions', TransactionController::class)->except('show');

    Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
    Route::get('/debts/{debt}/repayments/create', [DebtRepaymentController::class, 'create'])->name('debts.repayments.create');
    Route::post('/debts/{debt}/repayments', [DebtRepaymentController::class, 'store'])->name('debts.repayments.store');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
