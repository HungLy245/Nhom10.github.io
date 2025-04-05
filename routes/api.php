<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookController;
use App\Http\Controllers\API\BorrowController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\PackageController;
use App\Http\Controllers\API\SubscriptionController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\NotificationController;
use App\Models\Package;
use Illuminate\Support\Facades\Artisan;

Route::prefix('v1')->group(function () {
    // Public routes không cần middleware
    Route::prefix('open')->group(function () {
        // Books
        Route::controller(BookController::class)->group(function () {
            Route::get('/books/featured', 'featured');
            Route::get('/books/new', 'new');
            Route::get('/books/search', 'search');
            Route::get('/books/{id}', 'show');
            Route::get('/books', 'index');
        });
        
        // Categories
        Route::controller(CategoryController::class)->group(function () {
            Route::get('/categories', 'index');
            Route::get('/categories/{id}', 'show');
        });
        
        // Images
        Route::get('/images/{path}', function($path) {
            return response()->file(storage_path('app/public/books/' . $path));
        })->where('path', '.*');

        // Packages
        Route::controller(PackageController::class)->group(function () {
            Route::get('/packages', 'index');
            Route::get('/packages/{id}', 'show');
        });
    });

    // Auth routes
    Route::controller(AuthController::class)->group(function () {
        Route::post('/auth/register', 'register');
        Route::post('/auth/login', 'login');
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/auth/user', 'getUser');
            Route::post('/auth/logout', 'logout');
        });
        Route::post('/forgot-password', 'forgotPassword');
        Route::post('/reset-password', 'resetPassword');
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Users
        Route::prefix('user')->group(function () {
            Route::get('/profile', [AuthController::class, 'profile']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::put('/password', [AuthController::class, 'changePassword']);
            Route::get('/statistics', [AuthController::class, 'statistics']);
            Route::get('/activities', [AuthController::class, 'activities']);
        });

        // Subscriptions
        Route::prefix('subscription')->group(function () {
            Route::get('/current', [SubscriptionController::class, 'current']);
            Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
        });

        // Borrows
        Route::get('/user/borrows', [BorrowController::class, 'userBorrows']);
        Route::post('/borrows', [BorrowController::class, 'borrow']);
        Route::post('/borrows/{id}/return', [BorrowController::class, 'return'])->middleware('admin');
        Route::put('/books/{id}/quantity', [BorrowController::class, 'updateQuantity']);

        // Reservations
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/books/{id}/reserve', [ReservationController::class, 'reserve']);
        });
        Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);
        Route::get('/user/reservations', [ReservationController::class, 'myReservations']);

        // Reviews
        Route::get('/books/{book}/reviews', [ReviewController::class, 'bookReviews']);
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::get('/books/{book}/comments', [CommentController::class, 'bookComments']);
        Route::post('/comments', [CommentController::class, 'store']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    });

    Route::get('/schedule/run', function() {
        if (request()->get('key') !== config('app.cron_secret')) {
            abort(403);
        }
        Artisan::call('schedule:run');
        return 'Scheduler ran successfully';
    })->name('schedule.run');

    Route::get('/process-quantity-changes', function() {
        Artisan::call('books:process-quantity-changes');
        return response()->json(['message' => 'Processed successfully']);
    });
});