<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\SuperAdmin\ProductController;
use App\Http\Controllers\Api\PetController;
use App\Http\Controllers\Api\AuthController;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Pet Profile Routes
    Route::get('/pets', [PetController::class, 'index']);
    Route::post('/pets', [PetController::class, 'store']);
    Route::delete('/pets/{pet}', [PetController::class, 'destroy']);

    // SuperAdmin Routes
    Route::prefix('superadmin')->group(function () {
        Route::apiResource('products', ProductController::class);
    });
});
