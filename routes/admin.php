<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for the admin panel (backend).
| All routes here require authentication and email verification.
| Permission middleware will be added in Task 6.
|
*/

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::inertia('dashboard', 'admin/dashboard')->name('dashboard');
});
