<?php

namespace App\Http\Controllers;

use App\Models\Pokemon;
use App\Models\Move;
use App\Models\PokemonVariety;
use App\Models\PokemonEvolution;
use App\Models\Type;
use App\Models\TypeInteractionState;
use App\Models\GameVersion;
use App\Models\PokemonLearnMove;
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

        // Modifions la requête des versions pour ne prendre que celles où le Pokémon a des capacités
        $versions = GameVersion::whereExists(function ($query) use ($pokemonVarietyId) {
            $query->select('game_version_id')
                ->from('pokemon_learn_moves')
                ->whereColumn('game_versions.id', 'pokemon_learn_moves.game_version_id')
                ->where('pokemon_variety_id', $pokemonVarietyId);
        })
            ->with('translations')
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'name' => $version->translate(app()->getLocale())?->name,
                    'generic_name' => $version->generic_name
                ];
            });

        // Le reste du code reste identique...
        $moves = Move::whereIn('id', function ($query) use ($pokemonVarietyId) {
            $query->select('move_id')
                ->from('pokemon_learn_moves')
                ->where('pokemon_variety_id', $pokemonVarietyId);
        })
            ->with([
                'translations',
                'type',
                'moveDamageClass.translations'
            ])
            ->get()
            ->map(function ($move) use ($pokemonVarietyId) {
                $moveVersions = PokemonLearnMove::where('pokemon_variety_id', $pokemonVarietyId)
                    ->where('move_id', $move->id)
                    ->pluck('game_version_id')
                    ->toArray();

                return [
                    'id' => $move->id,
                    'name' => $move->translate(app()->getLocale())?->name,
                    'description' => $move->translate(app()->getLocale())?->description,
                    'power' => $move->power,
                    'accuracy' => $move->accuracy,
                    'pp' => $move->pp,
                    'type' => [
                        'name' => $move->type?->name,
                        'type_ball' => $move->type?->type_ball
                    ],
                    'class' => [
                        'id' => $move->moveDamageClass?->id,
                        'name' => $move->moveDamageClass?->translate(app()->getLocale())?->name
                    ],
                    'versions' => $moveVersions
                ];
            });

        return response()->json([
            'versions' => $versions,
            'moves' => $moves
        ]);
    }

    public function showability(Pokemon $pokemon)
    {
        $pokemon = Pokemon::with(['defaultVariety.abilities'])->find($pokemon->id);
        return response()->json($pokemon->defaultVariety->abilities);
    }

    private function getEvolutionMessage($evolution)
    {
        return match ($evolution->evolution_trigger_id) {
            1 => $evolution->min_level ? "Niveau {$evolution->min_level}" : "Par niveau",
            2 => "Par échange",
            3 => $evolution->item_id ? "Utiliser {$evolution->item_id}" : "Utiliser un objet",
            4 => "Par mue",
            5 => "En tournant",
            6 => "Tour des Ténèbres",
            7 => "Tour des Eaux",
            8 => "Trois coups critiques",
            9 => "Recevoir des dégâts",
            10 => "Autre méthode",
            11 => "Style Agile",
            12 => "Style Puissant",
            13 => "Dégâts de recul",
            default => "Méthode inconnue"
        };
    }

    private function getEvolutionChainForward($varietyId, &$chain = [])
    {
        $evolutions = PokemonEvolution::where('pokemon_variety_id', $varietyId)
            ->with([
                'pokemonVarietyId.pokemon',
                'pokemonVarietyId.sprites',
                'evolvesToPokemonId.pokemon',
                'evolvesToPokemonId.sprites',
                'evolutionTrigger.translations',
                'itemId',
                'heldItemId'
            ])
            ->get();

        foreach ($evolutions as $evolution) {
            $chain[] = [
                'from_pokemon' => [
                    'id' => $evolution->pokemonVarietyId?->pokemon?->id,
                    'name' => $evolution->pokemonVarietyId?->pokemon?->name,
                    'sprite_url' => $evolution->pokemonVarietyId?->sprites?->front_url
                ],
                'to_pokemon' => [
                    'id' => $evolution->evolvesToPokemonId?->pokemon?->id,
                    'name' => $evolution->evolvesToPokemonId?->pokemon?->name,
                    'sprite_url' => $evolution->evolvesToPokemonId?->sprites?->front_url
                ],
                'evolution_method' => $this->getDetailedEvolutionMessage($evolution)
            ];

            // Appel récursif pour trouver les évolutions suivantes
            $this->getEvolutionChainForward($evolution->evolves_to_id, $chain);
        }

        return $chain;
    }


    private function getEvolutionChainBackward($varietyId, &$chain = [])
    {
        $evolutions = PokemonEvolution::where('evolves_to_id', $varietyId)
            ->with([
                'pokemonVarietyId.pokemon',
                'pokemonVarietyId.sprites',
                'evolvesToPokemonId.pokemon',
                'evolvesToPokemonId.sprites',
                'evolutionTrigger.translations',
                'itemId',
                'heldItemId'
            ])
            ->get();

        foreach ($evolutions as $evolution) {
            $chain[] = [
                'from_pokemon' => [
                    'id' => $evolution->pokemonVarietyId?->pokemon?->id,
                    'name' => $evolution->pokemonVarietyId?->pokemon?->name,
                    'sprite_url' => $evolution->pokemonVarietyId?->sprites?->front_url
                ],
                'to_pokemon' => [
                    'id' => $evolution->evolvesToPokemonId?->pokemon?->id,
                    'name' => $evolution->evolvesToPokemonId?->pokemon?->name,
                    'sprite_url' => $evolution->evolvesToPokemonId?->sprites?->front_url
                ],
                'evolution_method' => $this->getDetailedEvolutionMessage($evolution)
            ];

            // Appel récursif pour trouver les évolutions précédentes
            $this->getEvolutionChainBackward($evolution->pokemon_variety_id, $chain);
        }

        return $chain;
    }

    public function evolutions(Pokemon $pokemon)
    {
        try {
            $variety = $pokemon->defaultVariety;
            if (!$variety) {
                return response()->json([]);
            }

            // Initialiser les tableaux pour les chaînes d'évolution
            $forwardChain = [];
            $backwardChain = [];

            // Récupérer toute la chaîne d'évolution vers l'avant et l'arrière
            $evolutionsTo = $this->getEvolutionChainForward($variety->id, $forwardChain);
            $evolutionsFrom = $this->getEvolutionChainBackward($variety->id, $backwardChain);

            return response()->json([
                'evolves_to' => collect($evolutionsTo)->filter()->values(),
                'evolves_from' => collect($evolutionsFrom)->filter()->values()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getDetailedEvolutionMessage($evolution)
    {
        $method = $evolution->evolutionTrigger?->translate(app()->getLocale())?->name ?? 'Méthode inconnue';
        $details = [];

        switch ($evolution->evolution_trigger_id) {
            case 1: // level-up
                if ($evolution->min_level) {
                    $details[] = "Niveau {$evolution->min_level}";
                }
                if ($evolution->min_happiness) {
                    $details[] = "Bonheur ≥ {$evolution->min_happiness}";
                }
                if ($evolution->min_affection) {
                    $details[] = "Affection ≥ {$evolution->min_affection}";
                }
                break;

            case 2: // trade
                if ($evolution->tradeSpeciesId) {
                    $details[] = "Échange contre {$evolution->tradeSpeciesId->name}";
                }
                if ($evolution->heldItemId) {
                    $details[] = "Tenir {$evolution->heldItemId->translate(app()->getLocale())?->name}";
                }
                break;

            case 3: // use-item
                if ($evolution->itemId) {
                    $details[] = "Utiliser {$evolution->itemId->translate(app()->getLocale())?->name}";
                }
                break;
        }

        if ($evolution->time_of_day) {
            $details[] = match ($evolution->time_of_day) {
                'day' => 'Jour',
                'night' => 'Nuit',
                default => ucfirst($evolution->time_of_day)
            };
        }

        if ($evolution->needs_overworld_rain) {
            $details[] = "Sous la pluie";
        }

        if ($evolution->turn_upside_down) {
            $details[] = "Console à l'envers";
        }

        if ($details) {
            return implode(' + ', $details);
        }

        return $method;
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

        $pokemonTypes = $variety->types;
        $allTypes = Type::orderBy('id')->get();
        $typeInteractions = [];

        foreach ($allTypes as $attackingType) {
            // Ignorer le type Stellaire
            if ($attackingType->id === 19) {
                continue;
            }
            $multiplier = 1.0;

            foreach ($pokemonTypes as $defendingType) {
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

            $typeInteractions[] = [
                'type' => $attackingType->name,
                'multiplier' => $multiplier,
                'type_ball' => $attackingType->type_ball,
                'background_color' => $this->getBackgroundColor($multiplier)
            ];
        }

        return response()->json($typeInteractions);
    }

    private function getBackgroundColor($multiplier)
    {
        return match ($multiplier) {
            0.0 => '#808080',  // Gris pour immunité
            0.25 => '#93FF77', // Vert clair pour très résistant
            0.5 => '#98FB98',  // Vert pâle pour résistant
            1.0 => '#FFF8DC',  // Beige clair pour normal
            2.0 => '#FFB6C1',  // Rose pour faible
            4.0 => '#FF7553',  // Orange-rouge pour très faible
            default => '#FFF8DC'
        };
    }
}
