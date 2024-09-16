<?php

namespace App\Http\Controllers;

use App\Models\Move;
use App\Models\Pokemon;
use Illuminate\Http\Request;

class MoveController extends Controller
{
    public function index()
    {
        return Move::with(['damageClass', 'types'])
            ->paginate(20);
    }

    public function getPokemonMoves(Pokemon $pokemon)
    {
        // Récupérer les varieties du Pokémon et les moves associés via la relation learnMoves
        $moves = $pokemon->varieties()
            ->with('learnMoves.move', 'learnMoves.moveLearnMethod', 'learnMoves.gameVersion')
            ->get()
            ->pluck('learnMoves') // Extraire la collection de learnMoves
            ->flatten() // Aplatir la collection pour avoir une liste de moves
            ->unique('move.id'); // Éviter les doublons de moves

        return response()->json($moves);
    }

}
