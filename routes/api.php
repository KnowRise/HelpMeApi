<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\Manage\AdminController;
use App\Http\Controllers\API\Manage\CategoryController;
use App\Http\Controllers\API\Manage\ChatController;
use App\Http\Controllers\API\Manage\MitraController;
use App\Http\Controllers\API\Manage\OrderController;
use App\Http\Controllers\API\Manage\RatingController;
use App\Http\Controllers\API\Manage\TransactionController;
use App\Http\Controllers\API\Manage\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    Route::prefix('admins')->middleware(['auth:sanctum', 'is.admin'])->group(function () {
        Route::post('/', [AdminController::class, 'createAdmin']);
        Route::delete('/', [AdminController::class, 'deleteAdmin']);
    });
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/me', [AuthController::class, 'aboutMe'])->middleware(['auth:sanctum']);
        Route::post('/verification', [AuthController::class, 'sendVerificationCode']);
        Route::post('/verify', [AuthController::class, 'verifyCode']);
        Route::post('/forgot-password', [AuthController::class, 'sendResetLink']);
        Route::post('/reset-password', [AuthController::class, 'reset']);
        Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware(['auth:sanctum', 'is.user.active']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'is.user.active']);
    });
    Route::prefix('categories')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [CategoryController::class, 'categoryList']);
        Route::middleware(['is.admin'])->group(function () {
            Route::post('/', [CategoryController::class, 'storeCategory']);
            Route::delete('/{id}', [CategoryController::class, 'deleteCategory']);
        });
        Route::prefix('helpers')->group(function () {
            Route::get('/', [CategoryController::class, 'helperList']);
            Route::middleware(['is.admin'])->group(function () {
                Route::post('/', [CategoryController::class, 'storeHelper']);
                Route::delete('/{id}', [CategoryController::class, 'deleteHelper']);
            });
        });
        Route::prefix('problems')->group(function () {
            Route::get('/', [CategoryController::class, 'problemList']);
            Route::middleware(['is.admin'])->group(function () {
                Route::post('/', [CategoryController::class, 'storeProblem']);
                Route::delete('/{id}', [CategoryController::class, 'deleteProblem']);
            });
        });
    });
    Route::prefix('mitras')->middleware(['auth:sanctum'])->group(function () {
        Route::prefix('withdraws')->group(function () {
            Route::get('/', [TransactionController::class, 'getWithdraw']);
            Route::post('/', [TransactionController::class, 'storeWithdraw'])->middleware(['is.mitra']);
        });
        Route::get('/{id?}', [MitraController::class, 'mitraList']);
        Route::post('/{id?}/status', [AdminController::class, 'VerifyMitra'])->middleware(['is.admin']);
        Route::middleware(['is.mitra', 'is.number.verified'])->group(function () {
            Route::post('/', [MitraController::class, 'storeMitra']);
            Route::post('/{id}', [MitraController::class, 'updateMitra']);
        });
    });
    Route::prefix('users')->middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [AdminController::class, 'getUsers'])->middleware(['is.admin']);
        Route::post('/profile', [AuthCOntroller::class, 'editProfile']);
        Route::post('/status/{id}', [AdminController::class, 'toggleAccountStatus'])->middleware(['is.admin']);
        Route::prefix('orders')->group(function () {
            Route::get('/{id?}', [OrderController::class, 'orderList']);
            Route::post('/', [OrderController::class, 'storeOrder'])->middleware(['is.client', 'is.number.verified']);
            Route::post('/mitra', [OrderController::class, 'selectMitra'])->middleware(['is.client', 'is.number.verified']);
            // Route::get('/{id}/status', [OrderController::class, 'getStatus'])->middleware(['is.client']);
            Route::post('/{id}/status', [OrderController::class, 'updateStatus'])->middleware(['is.mitra']);
        });
        Route::prefix('offers')->group(function () {
            Route::post('/{orderId}', [OrderController::class, 'offerOrder'])->middleware(['is.mitra', 'is.number.verified']);
            Route::get('/{orderId}', [OrderController::class, 'offerList'])->middleware(['is.client', 'is.number.verified']);
        });
        Route::prefix('/chats')->middleware(['is.number.verified'])->group(function () {
            Route::post('/', [ChatController::class, 'createOrGetChat']);
            Route::get('/{id}/messages', [ChatController::class, 'getMessages']);
            Route::post('/messages', [ChatController::class, 'sendMessage']);
        });
        Route::prefix('/transactions')->group(function () {
            Route::get('/{id?}', [TransactionController::class, 'TransactionList']);
            Route::post('/', [TransactionController::class, 'createTransaction'])->middleware(['is.client']);
            Route::post('/refund', [TransactionController::class, 'refundTransaction'])->middleware(['is.client']);
        });
        Route::prefix('ratings')->group(function () {
            Route::post('/', [RatingController::class, 'storeRating'])->middleware(['is.client']);
            Route::get('/mitra/{mitraId}', [RatingController::class, 'getMitraRatings']);
        });
    });

    Route::post('/notification', [TransactionController::class, 'notification']);
});

Route::post('/notip', [NotificationController::class, 'notip']);
