<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\IncidentController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard/plans', [PlanController::class, 'index'])->name('user.plans');

    Route::resource('/dashboard/monitors', MonitorController::class);

    Route::get('/checkout/{plan}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{plan}/process', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/success', function() {
        return Inertia::render('Checkout/Success');
    })->name('checkout.success');

    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
});

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

//Route::middleware('auth')->group(function () {
//    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
//    Route::post('/subscription/initiate', [SubscriptionController::class, 'initiate'])->name('subscription.initiate');
//});
//
//Route::post('/wayforpay/callback', [SubscriptionController::class, 'callback'])->name('wayforpay.callback');

// Webhooks (без auth middleware)
Route::post('/webhooks/wayforpay', [WebhookController::class, 'wayforpay'])
    ->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.wayforpay');

require __DIR__.'/auth.php';
