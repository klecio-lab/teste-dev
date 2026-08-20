<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PokemonMetricsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/pokemons/metrics', PokemonMetricsController::class);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
