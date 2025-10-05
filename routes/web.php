<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitorController;



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

    Route::get('/dashboard/plans', function () {
        $plans = [
            [
                'name' => 'FREE',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'currency' => 'USD',
                'description' => 'Forever free',
                'features' => [
                    ['label' => '1 monitor', 'available' => true],
                    ['label' => '5 minutes check interval', 'available' => true],
                    ['label' => 'SSL certificate monitoring', 'available' => false],
                ],
                'button' => 'FREE',
                'active' => false,
            ],
            [
                'name' => 'BUSINESS',
                'monthly_price' => 9.99,
                'yearly_price' => 99.99, // умовно 2 місяці безкоштовно
                'currency' => 'USD',
                'description' => 'For small teams',
                'features' => [
                    ['label' => '10 monitors', 'available' => true],
                    ['label' => '3 minutes check interval', 'available' => true],
                    ['label' => 'SSL certificate monitoring', 'available' => true],
                ],
                'button' => 'SUBSCRIBE',
                'active' => false,
            ],
            [
                'name' => 'ENTERPRISE',
                'monthly_price' => 99.99,
                'yearly_price' => 999.99,
                'currency' => 'USD',
                'description' => 'For large organizations',
                'features' => [
                    ['label' => '25 monitors', 'available' => true],
                    ['label' => '1 minute check interval', 'available' => true],
                    ['label' => 'SSL certificate monitoring', 'available' => true],
                ],
                'button' => 'ACTIVE',
                'active' => true,
            ],
        ];

        return Inertia::render('User/Plans', ['plans' => $plans]);
    })->name('user.plans');

    Route::resource('/dashboard/monitors', MonitorController::class);
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

require __DIR__.'/auth.php';
