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

// Favicon route to prevent 404 errors
Route::get('/favicon.ico', function () {
    return response()->file(public_path('favicon.ico'));
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $statsService = new \App\Services\Dashboard\DashboardStatsService();
        $creditService = new \App\Services\CreditService();

        $stats = $statsService->getUserStats($user);
        $creditStatus = $creditService->getUserCreditStatus($user->id);
        $creditStatus['demo_credits_remaining'] = $user->getRemainingDemoCredits();

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'creditStatus' => $creditStatus,
        ]);
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

    // Test route for GitHub error handling
    Route::get('test-github-error-handling', function () {
        return Inertia::render('Test/GitHubErrorHandlingTest');
    })->name('test-github-error-handling');

    // Test route for modal toast integration
    Route::get('test-modal-toast', function () {
        return Inertia::render('Test/ModalToastTest');
    })->name('test-modal-toast');

    // Test route for toast function verification
    Route::get('test-toast-functions', function () {
        return Inertia::render('Test/ToastFunctionTest');
    })->name('test-toast-functions');

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

    // History routes for viewing individual conversations and analysis results
    Route::get('thinktest/conversation/{id}', [ThinkTestController::class, 'showConversation'])->name('thinktest.conversation.show');
    Route::get('thinktest/analysis/{id}', [ThinkTestController::class, 'showAnalysis'])->name('thinktest.analysis.show');

    // Test infrastructure setup routes
    Route::post('thinktest/detect-infrastructure', [ThinkTestController::class, 'detectTestInfrastructure'])->name('thinktest.detect_infrastructure');
    Route::post('thinktest/download-template', [ThinkTestController::class, 'downloadTemplate'])->name('thinktest.download_template');

    // GitHub repository routes with authentication and rate limiting
    Route::middleware(['auth', 'github.rate_limit'])->group(function () {
        Route::post('thinktest/github/validate', [ThinkTestController::class, 'validateRepository'])->name('thinktest.github.validate');
        Route::post('thinktest/github/branches', [ThinkTestController::class, 'getRepositoryBranches'])->name('thinktest.github.branches');
        Route::post('thinktest/github/process', [ThinkTestController::class, 'processRepository'])->name('thinktest.github.process');

        // File browsing routes
        Route::post('thinktest/github/browse', [ThinkTestController::class, 'browseRepositoryContents'])->name('thinktest.github.browse');
        Route::post('thinktest/github/tree', [ThinkTestController::class, 'getRepositoryTree'])->name('thinktest.github.tree');
        Route::post('thinktest/github/file', [ThinkTestController::class, 'getFileContent'])->name('thinktest.github.file');

        // Single file test generation
        Route::post('thinktest/generate-single-file', [ThinkTestController::class, 'generateTestsForSingleFile'])->name('thinktest.generate_single_file');
    });

    // GitHub debug route (admin only)
    Route::get('thinktest/github/debug', [ThinkTestController::class, 'debugGitHub'])
        ->name('thinktest.github.debug')
        ->middleware('can:access dashboard');

    // Credit and Payment routes
    Route::prefix('credits')->name('credits.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PaymentController::class, 'index'])->name('index');
        Route::post('/payment-intent', [\App\Http\Controllers\PaymentController::class, 'createPaymentIntent'])->name('payment-intent');
        Route::get('/payment-status', [\App\Http\Controllers\PaymentController::class, 'paymentStatus'])->name('payment-status');
        Route::get('/success', [\App\Http\Controllers\PaymentController::class, 'success'])->name('success');
        Route::get('/canceled', [\App\Http\Controllers\PaymentController::class, 'canceled'])->name('canceled');
        Route::get('/transactions', [\App\Http\Controllers\PaymentController::class, 'transactions'])->name('transactions');
        Route::get('/receipt', [\App\Http\Controllers\PaymentController::class, 'receipt'])->name('receipt');
    });

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

        // Admin payment management routes
        Route::middleware(['permission:manage-payments'])->group(function () {
            Route::post('/payments/refund', [\App\Http\Controllers\PaymentController::class, 'refund'])->name('payments.refund');
        });
    });
});

// Stripe webhook route (outside auth middleware)
Route::post('/stripe/webhook', [\App\Http\Controllers\PaymentController::class, 'webhook'])->name('stripe.webhook');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
