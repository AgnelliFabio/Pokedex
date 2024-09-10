<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PokemonController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/pokemon', [PokemonController::class, 'index']);
Route::get('/pokemon/{pokemon}', [PokemonController::class, 'show']);
