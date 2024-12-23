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
        Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware(['auth:sanctum']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum']);
    });
    Route::prefix('categories')->middleware(['auth:sanctum'])->group(function () {
        Route::prefix('helpers')->group(function () {
            Route::get('/', [CategoryController::class, 'helperList']);
            Route::middleware(['is.admin'])->group(function () {
                Route::post('/{id?}', [CategoryController::class, 'storeHelper']);
                Route::delete('/{id}', [CategoryController::class, 'deleteHelper']);
            });
        });
        Route::prefix('problems')->group(function () {
            Route::get('/', [CategoryController::class, 'problemList']);
            Route::middleware(['is.admin'])->group(function () {
                Route::post('/{id?}', [CategoryController::class, 'storeProblem']);
                Route::delete('/{id}', [CategoryController::class, 'deleteProblem']);
            });
        });
        Route::get('/', [CategoryController::class, 'categoryList']);
        Route::middleware(['is.admin'])->group(function () {
            Route::post('/{id?}', [CategoryController::class, 'storeCategory']);
            Route::delete('/{id}', [CategoryController::class, 'deleteCategory']);
        });
    });
    Route::prefix('mitras')->middleware(['auth:sanctum'])->group(function () {
        // Route::prefix('withdraws')->group(function () {
        //     Route::get('/', [TransactionController::class, 'getWithdraw']);
        //     Route::post('/', [TransactionController::class, 'storeWithdraw'])->middleware(['is.mitra']);
        // });

        Route::get('/', [MitraController::class, 'mitraList']);
        Route::get('/history', [OrderController::class, 'orderHistoryForMitra'])->middleware(['is.mitra']);
        Route::post('/{id}/verify', [AdminController::class, 'VerifyMitra'])->middleware(['is.admin']);
        Route::middleware(['is.mitra'])->group(function () {
            Route::post('/', [MitraController::class, 'storeMitra']);
            Route::post('/{id}', [MitraController::class, 'updateMitra']);
        });
    });
    Route::prefix('users')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [AdminController::class, 'getUsers'])->middleware(['is.admin']);
        Route::post('/profile', [AuthCOntroller::class, 'editProfile']);
        Route::post('/status/{id}', [AdminController::class, 'toggleAccountStatus'])->middleware(['is.admin']);
        Route::prefix('orders')->middleware(['is.user.active', 'is.number.verified'])->group(function () {
            Route::get('/{id?}', [OrderController::class, 'orderList']);
            Route::post('/', [OrderController::class, 'storeOrder'])->middleware(['is.client']);
            Route::post('/mitra', [OrderController::class, 'selectMitra'])->middleware(['is.client']);
            Route::post('/{id}/status', [OrderController::class, 'updateStatus']);
        });
        Route::prefix('offers')->middleware(['is.user.active', 'is.number.verified'])->group(function () {
            Route::post('/{orderId}', [OrderController::class, 'offerOrder'])->middleware(['is.mitra']);
            Route::get('/{orderId}', [OrderController::class, 'offerList'])->middleware(['is.client']);
        });
        Route::prefix('/chats')->middleware(['is.user.active'])->group(function () {
            Route::post('/{id}', [ChatController::class, 'createOrGetChat']);
            Route::get('/{code}/messages', [ChatController::class, 'getMessages']);
            Route::post('/{code}/messages', [ChatController::class, 'sendMessage']);
        });
        Route::prefix('/transactions')->middleware(['is.user.active'])->group(function () {
            Route::get('/{id?}', [TransactionController::class, 'TransactionList']);
            Route::post('/', [TransactionController::class, 'createTransaction'])->middleware(['is.client']);
            // Route::post('/refund', [TransactionController::class, 'refundTransaction'])->middleware(['is.client']);
        });
        Route::prefix('ratings')->middleware(['is.user.active'])->group(function () {
            Route::post('/', [RatingController::class, 'storeRating'])->middleware(['is.client']);
            Route::get('/{mitraId}', [RatingController::class, 'getMitraRatings']);
        });
    });
    Route::post('/notification', [TransactionController::class, 'notification']);
});

// Route::post('/notip', [NotificationController::class, 'notip']);
