<?php

use App\Http\Controllers\ContributionController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('wishes/{wish}', [WishController::class, 'show'])->name('wishes.show');

Route::post('wishes/{wish}/contributions', [ContributionController::class, 'store'])
    ->middleware('authenticated')
    ->name('wishes.contributions.store');

Route::get('payments/callback', PaymentCallbackController::class)->name('payments.callback');
