<?php

use Illuminate\Support\Facades\Route;

use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return view('welcome');
});

 
Route::get('/auth/redirect', function () {
    return Socialite::driver('github')->stateless()->redirect();
});