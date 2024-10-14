<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PokemonController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/pokemon', [PokemonController::class, 'index']);
Route::get('/pokemon/{pokemon}', [PokemonController::class, 'show']);
Route::get('/pokemon/{pokemon}/moves', [PokemonController::class, 'showmoves']);
Route::get('/pokemon/{pokemon}/ability', [PokemonController::class, 'showability']);
