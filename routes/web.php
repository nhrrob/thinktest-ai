<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ThinkTestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Test route for toast notifications
    Route::get('test-toast', function () {
        $flashData = [];

        if (request('success')) {
            $flashData['success'] = request('success');
        }

        if (request('error')) {
            $flashData['error'] = request('error');
        }

        return Inertia::render('test-toast')->with($flashData);
    })->name('test-toast');

    // Test route for toast deduplication fix
    Route::get('test-toast-deduplication', function () {
        return Inertia::render('Test/ToastTest');
    })->name('test-toast-deduplication');

    // Test route for modal toast integration
    Route::get('test-modal-toast', function () {
        return Inertia::render('Test/ModalToastTest');
    })->name('test-modal-toast');

    // Session validation endpoint
    Route::get('auth/check', function () {
        $user = Auth::user();

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'csrf_token' => csrf_token(),
        ]);
    })->name('auth.check');

    // ThinkTest AI routes
    Route::get('thinktest', [ThinkTestController::class, 'index'])->name('thinktest.index');
    Route::post('thinktest/upload', [ThinkTestController::class, 'upload'])->name('thinktest.upload');
    Route::post('thinktest/generate', [ThinkTestController::class, 'generateTests'])->name('thinktest.generate');
    Route::get('thinktest/download', [ThinkTestController::class, 'downloadTests'])->name('thinktest.download');

    // Test infrastructure setup routes
    Route::post('thinktest/detect-infrastructure', [ThinkTestController::class, 'detectTestInfrastructure'])->name('thinktest.detect_infrastructure');
    Route::post('thinktest/download-template', [ThinkTestController::class, 'downloadTemplate'])->name('thinktest.download_template');

    // GitHub repository routes with rate limiting
    Route::middleware(['github.rate_limit'])->group(function () {
        Route::post('thinktest/github/validate', [ThinkTestController::class, 'validateRepository'])->name('thinktest.github.validate');
        Route::post('thinktest/github/branches', [ThinkTestController::class, 'getRepositoryBranches'])->name('thinktest.github.branches');
        Route::post('thinktest/github/process', [ThinkTestController::class, 'processRepository'])->name('thinktest.github.process');
    });

    // GitHub debug route (admin only)
    Route::get('thinktest/github/debug', [ThinkTestController::class, 'debugGitHub'])
        ->name('thinktest.github.debug')
        ->middleware('can:access dashboard');

    // Admin routes - Organized under admin prefix with proper namespace
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware(['permission:view roles|create roles|edit roles|delete roles'])->group(function () {
            Route::resource('roles', RoleController::class);
        });

        Route::middleware(['permission:view permissions|create permissions|edit permissions|delete permissions'])->group(function () {
            Route::resource('permissions', PermissionController::class);
        });

        Route::middleware(['permission:view users|create users|edit users|delete users'])->group(function () {
            Route::resource('users', UserController::class);
        });
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
