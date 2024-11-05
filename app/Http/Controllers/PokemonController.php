<?php

namespace App\Http\Controllers;

use App\Models\Pokemon;
use App\Models\Move;
use App\Models\Ability;
use Illuminate\Http\Request;

class PokemonController extends Controller
{
    // Display pokemons visuals | each page contains 20 pokemons
    public function index()
    {
        return Pokemon::with(['defaultVariety', 'defaultVariety.sprites', 'defaultVariety.types'])
            ->paginate(20);
    }

    // Display targetted pokemon (see route)
    public function show(Pokemon $pokemon)
    {
        return $pokemon->load(['defaultVariety', 'defaultVariety.sprites', 'defaultVariety.types']);
    }

    public function showVarieties(Pokemon $pokemon)
    {
        return $pokemon->varieties()->with(['sprites', 'types'])->get();
    }

    public function showmoves(Pokemon $pokemon)
    {
        $pokemonVarietyId = $pokemon->defaultVariety->id;

        return Move::whereIn('id', function ($query) use ($pokemonVarietyId) {
            $query->select('move_id')
                ->from('pokemon_learn_moves')
                ->where('pokemon_variety_id', $pokemonVarietyId);
        })->get();
    }

    public function showability(Pokemon $pokemon)
    {
        $pokemon = Pokemon::with(['defaultVariety.abilities'])->find($pokemon->id);
        return response()->json($pokemon->defaultVariety->abilities);
    }

    public function evolvesTree(Pokemon $pokemon)
    {
        $pokemon = Pokemon::with('defaultVariety.evolvesToId')->find($pokemon->id);
        return response()->json($pokemon->defaultVariety->evolvesToId);
    }
}
