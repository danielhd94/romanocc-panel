<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LawController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ArticleVisitController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\AppInfoController;
use App\Http\Controllers\Api\V2\LawController as LawControllerV2;
use App\Http\Controllers\Api\V2\RegulationController as RegulationControllerV2;
use App\Http\Controllers\Auth\PasswordResetController;
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

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/auth/reset-password', [PasswordResetController::class, 'resetPassword']);

// Information app routes (public)
Route::get('/app-info', [AppInfoController::class, 'index']);

// Forum routes (public for reading, protected for writing)
Route::get('/forum/topics', [ForumController::class, 'getTopics']);
Route::get('/forum/topics/{id}', [ForumController::class, 'getTopicDetail']);
Route::get('/forum/topics/{id}/comments', [ForumController::class, 'getComments']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    
    // Law routes
    Route::get('/laws', [LawController::class, 'index']);
    Route::get('/laws/{id}', [LawController::class, 'show']);

    // V2 Law routes (mobile)
    Route::prefix('v2')->group(function () {
        Route::get('/laws', [LawControllerV2::class, 'index']);
        Route::get('/laws/{id}', [LawControllerV2::class, 'show']);
        Route::get('/laws/{id}/detail', [LawControllerV2::class, 'detail']);
        
        // Regulation routes
        Route::get('/regulations', [RegulationControllerV2::class, 'index']);
        Route::get('/regulations/{id}', [RegulationControllerV2::class, 'show']);
        Route::get('/regulations/{id}/detail', [RegulationControllerV2::class, 'detail']);
        
        // Search routes
        Route::get('/search', [\App\Http\Controllers\Api\V2\SearchController::class, 'search']);
    });

    // Forum routes (protected for writing)
    Route::get('/forum/my-topics', [ForumController::class, 'getMyTopics']);
    Route::post('/forum/topics/create', [ForumController::class, 'createTopic']);
    Route::put('/forum/topics/{id}', [ForumController::class, 'updateTopic']);
    Route::delete('/forum/topics/{id}', [ForumController::class, 'deleteTopic']);
    Route::post('/forum/topics/{id}/comments/create', [ForumController::class, 'createComment']);
    Route::post('/forum/topics/{id}/reply-notification', [ForumController::class, 'sendReplyNotification']);
    
    // Search routes
    Route::get('/search', [SearchController::class, 'search']);
    
    // Article visit routes
    Route::post('/articles/{article}/visit', [ArticleVisitController::class, 'store']);
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/user/fcm-token', [NotificationController::class, 'updateFcmToken']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
}); 
