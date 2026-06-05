<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BookVersionController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\WorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - BriBooks Platform
|--------------------------------------------------------------------------
*/

// Auth (public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::get('/profile',       [AuthController::class, 'profile']);
    Route::post('/logout',       [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Books
    Route::apiResource('books', BookController::class);

    // Book versions (snapshot + rollback)
    Route::post('books/{book}/versions',                       [BookVersionController::class, 'store']);
    Route::get('books/{book}/versions',                        [BookVersionController::class, 'index']);
    Route::get('books/{book}/versions/{version}',              [BookVersionController::class, 'show']);
    Route::post('books/{book}/versions/{version}/restore',     [BookVersionController::class, 'restore']);

    // Chapters (nested under books + standalone for update/delete)
    Route::get('books/{book}/chapters',  [ChapterController::class, 'index']);
    Route::post('books/{book}/chapters', [ChapterController::class, 'store']);
    Route::put('chapters/{chapter}',     [ChapterController::class, 'update']);
    Route::delete('chapters/{chapter}',  [ChapterController::class, 'destroy']);

    // Pages (nested under chapters + standalone for update/delete)
    Route::get('chapters/{chapter}/pages',  [PageController::class, 'index']);
    Route::post('chapters/{chapter}/pages', [PageController::class, 'store']);
    Route::put('pages/{page}',              [PageController::class, 'update']);
    Route::delete('pages/{page}',           [PageController::class, 'destroy']);

    // Document upload & conversion
    Route::post('books/{book}/upload', [UploadController::class, 'upload']);

    // Workflow transitions
    Route::post('books/{book}/submit',  [WorkflowController::class, 'submit']);
    Route::post('books/{book}/approve', [WorkflowController::class, 'approve']);
    Route::post('books/{book}/reject',  [WorkflowController::class, 'reject']);
    Route::post('books/{book}/publish', [WorkflowController::class, 'publish']);
});
