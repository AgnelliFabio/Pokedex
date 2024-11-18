<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PokemonController;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/pokemon', [PokemonController::class, 'index']);
Route::get('/pokemon/{pokemon}', [PokemonController::class, 'show']);
Route::get('/pokemon/{pokemon}/moves', [PokemonController::class, 'showmoves']);
Route::get('/pokemon/{pokemon}/ability', [PokemonController::class, 'showability']);
Route::get('/pokemon/{pokemon}/evolution', [PokemonController::class, 'evolutions']);
Route::get('/pokemon/{pokemon}/weaknesses', [PokemonController::class, 'weaknesses']);
 
Route::get('/auth/redirect', function () {
    return Socialite::driver('github')->stateless()->redirect();
});
 
Route::get('/auth/callback', function () {
    // Récupérer les informations utilisateur depuis GitHub
    $githubUser = Socialite::driver('github')->stateless()->user();

    // Mettre à jour ou créer l'utilisateur dans la base de données
    $user = User::updateOrCreate(
        [
            'github_id' => $githubUser->id, // Identifier l'utilisateur avec son ID GitHub
        ],
        [
            'name' => $githubUser->name,
            'email' => $githubUser->email, // Utiliser l'adresse email de GitHub
            // Vous pouvez ajouter plus d'informations ici si nécessaire
        ]
    );

    // Générer un token pour Sanctum
    $token = $user->createToken('spa')->plainTextToken;

    // Retourner une réponse JSON avec le token
    return response()->json(['token' => $token], 200);
});