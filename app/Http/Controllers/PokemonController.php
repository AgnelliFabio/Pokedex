<?php

namespace App\Http\Controllers;

use App\Models\Pokemon;
use App\Models\Move;
use App\Models\PokemonVariety;
use App\Models\PokemonEvolution;
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

    // RECUPERATION DES EVOLUTIONS FUTURES ET PASSÉS DU POKEMON

    private function getEvolutionChainForward($varietyId, &$chain = [])
    {
        $evolutions = PokemonEvolution::where('pokemon_variety_id', $varietyId)->get();

        foreach ($evolutions as $evolution) {
            $chain[] = [
                'from_id' => $evolution->pokemon_variety_id,
                'to_id' => $evolution->evolves_to_id,
                'min_level' => $evolution->min_level
            ];

            $this->getEvolutionChainForward($evolution->evolves_to_id, $chain);
        }

        return $chain;
    }

    private function getEvolutionChainBackward($varietyId, &$chain = [])
    {
        $evolutions = PokemonEvolution::where('evolves_to_id', $varietyId)->get();

        foreach ($evolutions as $evolution) {
            $chain[] = [
                'from_id' => $evolution->pokemon_variety_id,
                'to_id' => $evolution->evolves_to_id,
                'min_level' => $evolution->min_level
            ];

            $this->getEvolutionChainBackward($evolution->pokemon_variety_id, $chain);
        }

        return $chain;
    }

    public function evolutions(Pokemon $pokemon)
    {
        $forwardChain = [];
        $backwardChain = [];

        $forward = $this->getEvolutionChainForward($pokemon->defaultVariety->id, $forwardChain);
        $backward = $this->getEvolutionChainBackward($pokemon->defaultVariety->id, $backwardChain);

        $enrichedForward = $this->enrichEvolutionChain($forward);
        $enrichedBackward = $this->enrichEvolutionChain($backward);

        return response()->json([
            'evolves_to' => $enrichedForward,
            'evolves_from' => $enrichedBackward
        ]);
    }

    private function enrichEvolutionChain($chain)
    {
        return collect($chain)->map(function ($evolution) {
            $fromVariety = PokemonVariety::with('pokemon')->find($evolution['from_id']);
            $toVariety = PokemonVariety::with('pokemon')->find($evolution['to_id']);

            return [
                'from_pokemon' => [
                    'id' => $fromVariety->pokemon->id,
                    'name' => $fromVariety->pokemon->name,
                    'sprite_url' => $fromVariety->sprites?->front_url
                ],
                'to_pokemon' => [
                    'id' => $toVariety->pokemon->id,
                    'name' => $toVariety->pokemon->name,
                    'sprite_url' => $toVariety->sprites?->front_url
                ],
                'min_level' => $evolution['min_level']
            ];
        });
    }
}
