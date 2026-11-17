<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1 (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public authentication routes
    Route::post('/register', [AuthController::class, 'register'])->name('api.v1.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.v1.login');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Authenticated user profile & logout
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('api.v1.me');

        // Project management endpoints
        Route::apiResource('projects', ProjectController::class)->names('api.v1.projects');

        // Task management under a project
        Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])->name('api.v1.projects.tasks.index');
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('api.v1.projects.tasks.store');

        // Direct task management endpoints
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('api.v1.tasks.show');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('api.v1.tasks.update');
        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('api.v1.tasks.status');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('api.v1.tasks.destroy');
    });
});
