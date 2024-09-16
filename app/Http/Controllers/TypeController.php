<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    public function index()
    {
        return Type::select('id')
            ->with('translations')
            ->get();
    }

    public function show($id)
    {
        $type = Type::findOrFail($id);

        $interactions = $type->interactedBy()
                             ->withPivot('type_interaction_state_id')
                             ->get();

        $weakness = [];
        $resistance = [];
        $normal = [];
        $invulnerable = [];

        foreach ($interactions as $interaction) {
            $multiplier = $interaction->pivot->typeInteractionState->multiplier;

            if ($multiplier == 2) {
                $weakness[] = $interaction;
            } elseif ($multiplier == 0.5) {
                $resistance[] = $interaction;
            } elseif ($multiplier == 1) {
                $normal[] = $interaction;
            } elseif ($multiplier == 0) {
                $invulnerable[] = $interaction;
            }
        }

        return response()->json([
            'Weakness' => $weakness,
            'Resistance' => $resistance,
            'Normal' => $normal,
            'Invulnerable' => $invulnerable
        ]);
    }
}
