<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LawController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\InformationAppController;
use App\Http\Controllers\Api\ArticleVisitController;
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

// Information app routes (public)
Route::get('/app/information', [InformationAppController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    
    // Law routes
    Route::get('/laws', [LawController::class, 'index']);
    Route::get('/laws/{id}', [LawController::class, 'show']);
    
    // Search routes
    Route::get('/search', [SearchController::class, 'search']);
    
    // Article visit routes
    Route::post('/articles/{article}/visit', [ArticleVisitController::class, 'store']);
}); 