<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TestingController;


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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    //put routes here
    Route::get('/test-security', function () {
        return response()->json([
            'message' => 'security'], 200);
    });

    // payments routes
    Route::post('/payments', [PaymentController::class, 'generatePayment']);
    Route::get('/payments', [PaymentController::class, 'getPaymentDetails']);
    Route::get('/payments/{paymentId}', [PaymentController::class, 'getPaymentDetails']);
});

Route::post("/register", [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/testconnection', [TestingController::class, 'test']);
