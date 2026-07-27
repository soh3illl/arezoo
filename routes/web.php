<?php

use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('wishes/{wish}', [WishController::class, 'show'])->name('wishes.show');
