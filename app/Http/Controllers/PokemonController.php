<?php

namespace App\Http\Controllers;

use App\Models\Pokemon;
use Illuminate\Http\Request;

class PokemonController extends Controller
{
    public function index()
    {
        return Pokemon::with(['defaultVariety', 'defaultVariety.sprites'])
            ->paginate(20);
    }

    public function show(Pokemon $pokemon)
    {
        return $pokemon->load(['defaultVariety', 'defaultVariety.sprites', 'defaultVariety.types']);
    }

    public function search(Request $request)
    {
        return Pokemon::search($request->input('query'))
            ->get()
            ->load(['defaultVariety', 'defaultVariety.sprites', 'defaultVariety.types']);
    }
}
