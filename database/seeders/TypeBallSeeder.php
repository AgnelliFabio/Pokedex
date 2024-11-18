<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeBallSeeder extends Seeder
{
    public function run()
    {
        $types = [
            'Acier' => 'type_acier.png',
            'Électrik' => 'type_electrik.png',
            'Ténèbres' => 'type_tenebres.png',
            'Eau' => 'type_eau.png',
            'Feu' => 'type_feu.png',
            'Plante' => 'type_plante.png',
            'Insecte' => 'type_insecte.png',
            'Combat' => 'type_combat.png',
            'Dragon' => 'type_dragon.png',
            'Fée' => 'type_fee.png',
            'Glace' => 'type_glace.png',
            'Normal' => 'type_normal.png',
            'Poison' => 'type_poison.png',
            'Psy' => 'type_psy.png',
            'Roche' => 'type_roche.png',
            'Sol' => 'type_sol.png',
            'Spectre' => 'type_spectre.png',
            'Vol' => 'type_vol.png',
        ];

        foreach ($types as $name => $fileName) {
            $type = DB::table('type_translations')
                ->where('name', $name)
                ->where('locale', 'fr')
                ->first();

            if ($type) {
                DB::table('types')
                    ->where('id', $type->type_id)
                    ->update(['type_ball' => 'type-balls/' . $fileName]);
            }
        }
    }
}

