<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeBallSeeder extends Seeder
{
    public function run()
    {
        // Liste des types avec accents et majuscules (comme dans la BDD) et correspondance avec les fichiers PNG (sans accent)
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
            // Trouver l'ID du type dans `types` en utilisant la traduction française
            $type = DB::table('type_translations')
                ->where('name', $name)
                ->where('locale', 'fr')
                ->first();

            if ($type) {
                // Mettre à jour la colonne `type_ball` dans la table `types`
                DB::table('types')
                    ->where('id', $type->type_id)
                    ->update(['type_ball' => 'type-balls/' . $fileName]);
            }
        }
    }
}

