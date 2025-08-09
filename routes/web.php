<?php

use App\Http\Controllers\ThinkTestController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // ThinkTest AI routes
    Route::get('thinktest', [ThinkTestController::class, 'index'])->name('thinktest.index');
    Route::post('thinktest/upload', [ThinkTestController::class, 'upload'])->name('thinktest.upload');
    Route::post('thinktest/generate', [ThinkTestController::class, 'generateTests'])->name('thinktest.generate');
    Route::get('thinktest/download', [ThinkTestController::class, 'downloadTests'])->name('thinktest.download');

    // Admin routes - Roles and Permissions
    Route::middleware(['permission:manage roles'])->group(function () {
        Route::resource('roles', RoleController::class);
    });

    Route::middleware(['permission:manage permissions'])->group(function () {
        Route::resource('permissions', PermissionController::class);
    });

    Route::middleware(['permission:manage users'])->group(function () {
        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
