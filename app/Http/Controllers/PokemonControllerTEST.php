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
        // récupere les informations des pokémons et les sprites
        $pokemonWithRelations = $pokemon->load(['defaultVariety', 'defaultVariety.sprites', 'defaultVariety.types']);

        // ****************** Types Interractions *********************
        // Créer les différentes relations de types
        $weakness = collect();
        $resistance = collect();
        // $normal = collect();
        $invulnerable = collect();
        // Pour les types du personnages on regarde les interactions avec tout les types
        foreach ($pokemonWithRelations->defaultVariety->types as $type) {
            $interactions = $type->interactedBy()->get();

            foreach ($interactions as $interaction) {
                $interactionStateId = $interaction->pivot->type_interaction_state_id;

                switch ($interactionStateId) {
                    case 1:
                        $invulnerable->push($interaction->name);  
                        break;
                    case 2:
                        $resistance->push($interaction->name);  
                        break;
                    case 4:
                        $weakness->push($interaction->name);  
                        break;
                    // default:
                    //     $normal->push($interaction->name);  
                    //     break;
                }
            }
        }
        // ---------------- FIN Types Interractions --------------------
        return response()->json([
            'pokemon' => $pokemonWithRelations,
            'type_interactions' => [
                'Weakness' => $weakness->unique()->values(),  
                'Resistance' => $resistance->unique()->values(),  
                //'Normal' => $normal->unique()->values(),  
                'Invulnerable' => $invulnerable->unique()->values(),  
            ]
        ]);
    }

    public function showVarieties(Pokemon $pokemon)
    {
        return $pokemon->varieties()->with(['sprites', 'types'])->get();
    }
}
