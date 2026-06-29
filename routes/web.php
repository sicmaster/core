<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::group([
    'namespace' => 'Laravel\Fortify\Http\Controllers',
    'domain' => config('fortify.domain', null),
    'prefix' => config('fortify.prefix'),
], function () {
    require __DIR__.'/fortify.php';
});
