<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\PublicController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ✅ Protected API route for React
Route::middleware(['web', 'customer.auth'])->group(function () {
    Route::get('/location/{id}/menu', [PublicController::class, 'locationMenuApi']);
});
