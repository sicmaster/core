<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\SystemSettingController;
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

    Route::get('users', [UserController::class, 'index'])
        ->middleware('permission:users.read')
        ->name('users.index');
        
    Route::get('users/create', [UserController::class, 'create'])
        ->middleware('permission:users.create')
        ->name('users.create');
        
    Route::post('users', [UserController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');
        
    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.update')
        ->name('users.edit');
        
    Route::put('users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.update')
        ->name('users.update');
        
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');
        
    Route::get('users-export', [ExportController::class, 'exportUsers'])
        ->middleware('permission:users.read')
        ->name('users.export');

    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.read')
        ->name('roles.index');
        
    Route::get('roles/create', [RoleController::class, 'create'])
        ->middleware('permission:roles.create')
        ->name('roles.create');
        
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('roles.store');
        
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('permission:roles.update')
        ->name('roles.edit');
        
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.update')
        ->name('roles.update');
        
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('roles.destroy');
        
    Route::get('roles-export', [ExportController::class, 'exportRoles'])
        ->middleware('permission:roles.read')
        ->name('roles.export');
        
    // System Settings
    Route::get('system-settings', [SystemSettingController::class, 'edit'])
        ->middleware('permission:settings.read')
        ->name('system-settings.edit');
        
    Route::put('system-settings', [SystemSettingController::class, 'update'])
        ->middleware('permission:settings.update')
        ->name('system-settings.update');
});
