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
        $moves = $pokemon->varieties()
            ->with('learnMoves.move', 'learnMoves.moveLearnMethod', 'learnMoves.gameVersion')
            ->get()
            ->pluck('learnMoves') 
            ->flatten()
            ->unique('move.id');

        return response()->json($moves);
    }
}
