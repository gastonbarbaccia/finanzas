<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DebtPaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::patch('/expenses/{expense}/paid', [ExpenseController::class, 'togglePaid'])->name('expenses.toggle-paid');
    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::resource('incomes', IncomeController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('debts', DebtController::class);
    Route::post('/debts/{debt}/payments', [DebtPaymentController::class, 'store'])->name('debts.payments.store');
    Route::post('/debts/{debt}/settle', [DebtPaymentController::class, 'settle'])->name('debts.settle');
    Route::delete('/debts/{debt}/payments/{payment}', [DebtPaymentController::class, 'destroy'])->name('debts.payments.destroy');

    // Asesor IA: chat con historial
    Route::get('/ai', [AiChatController::class, 'index'])->name('ai.index');
    Route::post('/ai/chat', [AiChatController::class, 'store'])->middleware('throttle:20,1')->name('ai.chat.store');
    Route::get('/ai/chat/{conversation}', [AiChatController::class, 'show'])->name('ai.chat.show');
    Route::post('/ai/chat/{conversation}', [AiChatController::class, 'message'])->middleware('throttle:20,1')->name('ai.chat.message');
    Route::delete('/ai/chat/{conversation}', [AiChatController::class, 'destroy'])->name('ai.chat.destroy');

    // Asesor IA: informes completos
    Route::get('/ai/informes', [AiController::class, 'index'])->name('ai.reports');
    Route::post('/ai/informes', [AiController::class, 'generate'])->middleware('throttle:5,1')->name('ai.generate');
    Route::delete('/ai/informes/{recommendation}', [AiController::class, 'destroy'])->name('ai.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf', [ReportController::class, 'download'])->middleware('throttle:10,1')->name('reports.download');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/usd-rate', [SettingsController::class, 'refreshRate'])->middleware('throttle:10,1')->name('settings.usd-rate');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
