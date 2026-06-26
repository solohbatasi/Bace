<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('guest');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    Route::apiResource('students', StudentController::class)
        ->names('api.students')
        ->middleware('permission:students.view')
        ->except(['store', 'update', 'destroy']);
    Route::post('students', [StudentController::class, 'store'])->middleware('permission:students.add|students.create');
    Route::match(['put', 'patch'], 'students/{student}', [StudentController::class, 'update'])->middleware('permission:students.edit|students.update');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->middleware('permission:students.delete');
});
