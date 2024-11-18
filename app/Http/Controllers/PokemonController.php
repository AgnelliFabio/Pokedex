<?php

namespace App\Http\Controllers;

use App\Models\Pokemon;
use App\Models\Move;
use App\Models\PokemonVariety;
use App\Models\PokemonEvolution;
use App\Models\Type;
use App\Models\TypeInteractionState;
use App\Models\Ability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    // RECUPERATION DES FAIBLESSES D'UN POKEMON


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

    public function weaknesses(Pokemon $pokemon)
    {

        $variety = $pokemon->defaultVariety;
        if (!$variety) {
            return response()->json([]);
        }

        // Récupérer les types du Pokémon
        $pokemonTypes = $variety->types;
        if ($pokemonTypes->isEmpty()) {
            return response()->json([]);
        }

        // Récupérer tous les types possibles
        $allTypes = Type::all();
        $typeMultipliers = [];

        foreach ($allTypes as $attackingType) {
            $multiplier = 1.0;

            foreach ($pokemonTypes as $defendingType) {
                // Utiliser la relation pour obtenir l'interaction
                $interaction = $attackingType->interactTo()
                    ->where('type_interactions.to_type_id', $defendingType->id)
                    ->first();

                if ($interaction && $interaction->pivot->type_interaction_state_id) {
                    $state = TypeInteractionState::find($interaction->pivot->type_interaction_state_id);
                    if ($state) {
                        $multiplier *= $state->multiplier;
                    }
                }
            }

            if ($multiplier != 1.0) {
                $typeMultipliers[$attackingType->name] = [
                    'multiplier' => $multiplier,
                    'category' => $this->getCategoryFromMultiplier($multiplier),
                    'sprite_url' => $attackingType->sprite_url
                ];
            }
        }

        // Organiser les résultats
        $organizedWeaknesses = [
            'immunities' => [],
            'quarter_damage' => [],
            'half_damage' => [],
            'double_damage' => [],
            'quadruple_damage' => []
        ];

        foreach ($typeMultipliers as $typeName => $data) {
            if ($data['category']) {
                $organizedWeaknesses[$data['category']][] = [
                    'type' => $typeName,
                    'multiplier' => $data['multiplier'],
                    'sprite_url' => $data['sprite_url']
                ];
            }
        }

        return response()->json($organizedWeaknesses);
    }

    private function getCategoryFromMultiplier($multiplier)
    {
        return match ($multiplier) {
            0.0 => 'immunities',
            0.25 => 'quarter_damage',
            0.5 => 'half_damage',
            2.0 => 'double_damage',
            4.0 => 'quadruple_damage',
            default => null
        };
    }
}
