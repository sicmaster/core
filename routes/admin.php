<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for the admin panel (backend).
| All routes here require authentication, email verification,
| and the "access admin" permission (role: admin or staff).
|
*/

Route::middleware(['auth', 'verified', 'permission:access admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::inertia('dashboard', 'admin/dashboard')->name('dashboard');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
});
