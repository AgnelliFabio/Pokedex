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
        $pokemon = Pokemon::with(['defaultVariety.abilities' => function ($query) {
            $query->with('translations');
        }])->find($pokemon->id);

        return $pokemon->defaultVariety->abilities->map(function ($ability) {
            $currentLocale = app()->getLocale();
            return [
                'id' => $ability->id,
                'name' => $ability->translate($currentLocale)?->name,
                'description' => $ability->translate($currentLocale)?->description,
                'effect' => $ability->translate($currentLocale)?->effect,
                'is_hidden' => $ability->pivot->is_hidden ?? false
            ];
        });
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

        // Récupérer toutes les évolutions liées à cette espèce
        $allEvolutions = PokemonEvolution::whereIn('pokemon_variety_id', function($query) use ($variety) {
            // Trouver toutes les variétés qui font partie de la même chaîne d'évolution
            $query->select('id')
                ->from('pokemon_varieties')
                ->where('pokemon_id', function($subquery) use ($variety) {
                    $subquery->select('pokemon_id')
                        ->from('pokemon_varieties')
                        ->where('id', $variety->id);
                });
        })
        ->orWhereIn('evolves_to_id', function($query) use ($variety) {
            // Même chose pour les évolutions vers ces variétés
            $query->select('id')
                ->from('pokemon_varieties')
                ->where('pokemon_id', function($subquery) use ($variety) {
                    $subquery->select('pokemon_id')
                        ->from('pokemon_varieties')
                        ->where('id', $variety->id);
                });
        })
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

        // Construire la chaîne d'évolution
        $chain = $allEvolutions->map(function($evolution) {
            return [
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
        });

        // Trier la chaîne pour avoir le bon ordre
        $sortedChain = $this->sortEvolutionChain($chain);

        return response()->json([
            'evolution_chain' => $sortedChain
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

private function sortEvolutionChain($chain)
{
    // Créer un graphe des évolutions
    $graph = [];
    $allPokemon = [];
    foreach ($chain as $evolution) {
        $fromId = $evolution['from_pokemon']['id'];
        $toId = $evolution['to_pokemon']['id'];
        $graph[$fromId][] = ['id' => $toId, 'evolution' => $evolution];
        $allPokemon[$fromId] = $evolution['from_pokemon'];
        $allPokemon[$toId] = $evolution['to_pokemon'];
    }

    // Trouver la forme de base (celle qui n'est destination d'aucune évolution)
    $baseForm = null;
    foreach ($allPokemon as $id => $pokemon) {
        $isDestination = false;
        foreach ($graph as $evolutions) {
            foreach ($evolutions as $evolution) {
                if ($evolution['id'] === $id) {
                    $isDestination = true;
                    break 2;
                }
            }
        }
        if (!$isDestination) {
            $baseForm = $id;
            break;
        }
    }

    // Construire la chaîne ordonnée
    $orderedChain = [];
    $currentId = $baseForm;
    while (isset($graph[$currentId])) {
        foreach ($graph[$currentId] as $evolution) {
            $orderedChain[] = $evolution['evolution'];
            $currentId = $evolution['id'];
            break;
        }
    }

    return $orderedChain;
}

    private function buildFullEvolutionChain($varietyId, &$chain)
    {
        // Trouver toutes les connexions d'évolution pour cette variété
        $evolutions = PokemonEvolution::where('pokemon_variety_id', $varietyId)
            ->orWhere('evolves_to_id', $varietyId)
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
