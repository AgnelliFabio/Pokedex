<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PokemonController;
use App\Http\Controllers\MoveController;
use App\Http\Controllers\TypeController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => 'pokemon'], function () {
    Route::get('/', [PokemonController::class, 'index']);
    Route::get('/{pokemon}', [PokemonController::class, 'show']);
    Route::get('/{pokemon}/varieties', [PokemonController::class, 'showVarieties']);
});

Route::group(['prefix'=> 'move'],function (){
    Route::get('/', [MoveController::class, 'index']);
    Route::get('/{pokemon}', [MoveController::class, 'getPokemonMoves']);
});

Route::group(['prefix' => 'type'], function () {
    Route::get('/', [TypeController::class, 'index']);
    Route::get('/{id}', [TypeController::class, 'show']);
});