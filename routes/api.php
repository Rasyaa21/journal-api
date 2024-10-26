<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\JournalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/index', [JournalController::class, 'index']);
    Route::post('/journal', [JournalController::class, 'store']);
    Route::get('/index/{post_id}', [JournalController::class, 'findSpecificItem']);
    Route::patch('/journal/{post_id}', [JournalController::class, 'update']);
    Route::delete('/journal/{post_id}', [JournalController::class, 'delete']);
    Route::delete('/logout', [AuthenticationController::class, 'revokeToken']);
    Route::get('/user', [AuthenticationController::class, 'userData']);
});

Route::post('/login', [AuthenticationController::class, 'loginRequest']);
Route::post('/register', [AuthenticationController::class, 'registerRequest']);
