<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EstatusController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/admin', [AuthController::class, 'loginAdmin']);
Route::post('/login/user', [AuthController::class, 'loginUser']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('estatus', EstatusController::class);

    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Admin access granted']);
    })->middleware('role:admin');
});
