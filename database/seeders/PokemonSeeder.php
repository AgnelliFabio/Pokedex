<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Pokemon;
use App\Models\PokemonEvolution;
use App\Models\PokemonLearnMove;
use App\Models\PokemonVarietySprite;
use App\Models\TypeInteraction;
use App\Models\TypeInteractionState;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PokemonSeeder extends Seeder
{
    private \GuzzleHttp\Client $client;

    private $translationsBridge = [
        'ja-Hrkt' => 'ja-hrkt',
        'roomaji' => 'ja-roj',
        'ko' => 'ko',
        'zh-Hant' => 'zh-hant',
        'fr' => 'fr',
        'de' => 'de',
        'es' => 'es',
        'it' => 'it',
        'en' => 'en',
        'cs' => 'cs',
        'zh-Hans' => 'zh-hans',
    ];

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedTypes();
        $this->seedMoveDamageClasses();
        $this->seedMoves();
        $this->seedMoveLearnMethods();
        $this->seedGameVersions();
        $this->seedAbilities();
        $this->seedEvolutionTrigger();
        $this->seedItems();
        $this->seedPokemon();
        $this->seedEvolutions();
    }

    // API Request

    public function request($url)
    {
        $response = $this->client->request('GET', $url);
        return json_decode($response->getBody());
    }

    public function requestPokeApi($path, $useLimit = false)
    {
        $url = 'https://pokeapi.co/api/v2/' . $path;

        if ($useLimit) {
            $url .= '?limit=100000&offset=0';
        }
        return $this->request($url);
    }

    public function getClass($path, $fn){
        $this->command->info('⏳ Fetching ' . $path . '...');

        $datas = $this->requestPokeApi($path, true);

        $errors = [];

        $progressBar = $this->command->getOutput()->createProgressBar($datas->count);
        foreach ($datas->results as $data) {

            try {
                $fn($data->url);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
                Log::error($e->getMessage());
                Log::warning($e->getTraceAsString());
            }
            $progressBar->advance();
        }
        $progressBar->finish();

        if (count($errors) > 0) {
            $this->command->error('❌ ' . $path . ' fetched with errors.');
            foreach ($errors as $error) {
                $this->command->error($error);
            }
            return;
        }

        //$this->command->info(PHP_EOL . '✅ ' . $path . ' fetched.');
    }

    public function getObject($url, $fn, $nameColumn = 'name')
    {
        $data = $this->request($url);

        // if (isset ($data->$nameColumn)) {
        //     $this->command->info('  🔵 Fetching ' . $data->$nameColumn . '...');
        // }else {
        //     $this->command->info('  🔵 Fetching...');
        // }

        return $fn($data);
    }

    public function searchTranslationAttribute($locale, $attribute, $name){
        for ($i = 0; $i < count($this->$attribute); $i++) {
            if ($this->$attribute[$i]->language->name === $locale) {
                return $this->$attribute[$i]->$name;
            }
        }
        return null;
    }

    public function addTranslations($data, $attributes)
    {
        $translations = [];

        foreach ($this->translationsBridge as $key => $value) {
            $currentLocale = [];
            foreach ($attributes as $attribute) {
                $listName = $attribute['listName'];
                $name = $attribute['name'];
                $localName = $attribute['localName'];

                if (property_exists($data, $listName) && is_array($data->$listName)) {
                    foreach ($data->$listName as $item) {
                        if (isset($item->language->name) && $item->language->name === $key && isset($item->$name)) {
                            $currentLocale[$localName] = $item->$name;
                            break;
                        }
                    }
                }
            }

            if (!empty($currentLocale)) {
                $translations[$value] = $currentLocale;
            }
        }

        return $translations;
    }

    public function saveTranslations($model, $apiData, $translations){
        $sortedTranslations = $this->addTranslations($apiData, $translations);

        foreach ($sortedTranslations as $locale => $fields) {
                foreach ($fields as $field => $value) {
                    $model->translateOrNew($locale)->{$field} = $value;
                }
        }

        $model->save();

        return $model;
    }

    public function getBaseStat($stats, $statName)
    {
        foreach ($stats as $stat) {
            if ($stat->stat->name === $statName) {
                return $stat->base_stat;
            }
        }
    }

    public function romanToInt($roman) {
        $roman = strtoupper($roman);

        $romanValues = [
            'I' => 1,
            'V' => 5,
            'X' => 10,
            'L' => 50,
            'C' => 100,
            'D' => 500,
            'M' => 1000
        ];

        $result = 0;
        $prevValue = 0;

        // On parcourt la chaîne de droite à gauche
        for ($i = strlen($roman) - 1; $i >= 0; $i--) {
            $currentValue = $romanValues[$roman[$i]];

            if ($currentValue >= $prevValue) {
                $result += $currentValue;
            } else {
                $result -= $currentValue;
            }

            $prevValue = $currentValue;
        }

        return $result;
    }

    public function safelyGetId($model, $translationKey, $name) {
        if (empty($name)) return null;
        $item = $model::whereTranslation($translationKey, $name)->first();
        return $item ? $item->id : null;
    }

    // table seeders

    public function seedTypes()
    {
        // store type interactions
        TypeInteractionState::updateOrCreate([
            'id' => 1,
        ], [
            'name' => 'immune',
            'multiplier' => 0,
        ]);
        TypeInteractionState::updateOrCreate([
            'id' => 2,
        ], [
            'name' => 'not_very_effective',
            'multiplier' => 0.5,
        ]);
        TypeInteractionState::updateOrCreate([
            'id' => 3,
        ], [
            'name' => 'normal',
            'multiplier' => 1,
        ]);
        TypeInteractionState::updateOrCreate([
            'id' => 4,
        ], [
            'name' => 'super_effective',
            'multiplier' => 2,
        ]);

        $dammageRelations = [
            'double_damage_to' => 'super_effective',
            'half_damage_to' => 'not_very_effective',
            'no_damage_to' => 'immune',
        ];

        // store types
        $this->getClass('type', function ($url) use ($dammageRelations) {
            $this->getObject($url, function ($type) use ($dammageRelations) {
                $spriteUrl = $type->sprites->{'generation-ix'}->{'scarlet-violet'}->name_icon;
                if ($spriteUrl){
                    $localType = \App\Models\Type::updateOrCreate([
                        'id' => $type->id,
                    ], [
                        'sprite_url' => $type->sprites->{'generation-ix'}->{'scarlet-violet'}->name_icon,
                    ]);

                    $this->saveTranslations($localType, $type, [
                        [
                            'listName' => 'names',
                            'name' => 'name',
                            'localName' => 'name',
                        ],
                    ]);
                }
            });
        });

        // store type interactions
        $this->getClass('type', function ($url) use ($dammageRelations) {
            $this->getObject($url, function ($type) use ($dammageRelations) {
                $localType = \App\Models\Type::find($type->id);

                foreach ($dammageRelations as $key => $value) {
                    if (property_exists($type->damage_relations, $key)) {
                        foreach ($type->damage_relations->$key as $relation) {
                            $toType = \App\Models\Type::whereTranslation('name', $relation->name)->first();

                            if ($toType) {
                                TypeInteraction::updateOrCreate([
                                    'from_type_id' => $localType->id,
                                    'to_type_id' => $toType->id,
                                ], [
                                    'type_interaction_state_id' => TypeInteractionState::where('name', $value)->first()->id,
                                ]);
                            }
                        }
                    }
                }
            });
        });
    }

    public function seedMoveDamageClasses(){
        $this->getClass('move-damage-class', function ($url) {
            $this->getObject($url, function ($moveDamageClass){
                $localMoveDamageClass = \App\Models\MoveDamageClass::updateOrCreate([
                    'id' => $moveDamageClass->id,
                ], []);

                $this->saveTranslations($localMoveDamageClass, $moveDamageClass, [
                    [
                        'listName' => 'names',
                        'name' => 'name',
                        'localName' => 'name',
                    ],
                    [
                        'listName' => 'descriptions',
                        'name' => 'description',
                        'localName' => 'description',
                    ]
                ]);
            });
        });
    }

    public function seedMoves()
    {
        $this->getClass('move', function ($url) {
            $this->getObject($url, function ($move){
                $damageClass = \App\Models\MoveDamageClass::whereTranslation('name', $move->damage_class->name)->first();
                $type = \App\Models\Type::whereTranslation('name', $move->type->name)->first();

                if (!$damageClass || !$type) {
                    return;
                }

                $localMove = \App\Models\Move::updateOrCreate([
                    'id' => $move->id,
                ], [
                    'power' => $move->power,
                    'pp' => $move->pp,
                    'priority' => $move->priority,
                    'accuracy' => $move->accuracy,
                    'move_damage_class_id' => $damageClass->id,
                    'type_id' => $type->id,
                ]);

                $this->saveTranslations($localMove, $move, [
                    [
                        'listName' => 'names',
                        'name' => 'name',
                        'localName' => 'name',
                    ],
                    [
                        'listName' => 'flavor_text_entries',
                        'name' => 'flavor_text',
                        'localName' => 'description',
                    ]
                ]);
            });
        });
    }

    public function seedMoveLearnMethods()
    {
        $this->getClass('move-learn-method', function ($url) {
            $this->getObject($url, function ($moveLearnMethod){
                $localMoveLearnMethod = \App\Models\MoveLearnMethod::updateOrCreate([
                    'id' => $moveLearnMethod->id,
                ], []);

                $this->saveTranslations($localMoveLearnMethod, $moveLearnMethod, [
                    [
                        'listName' => 'names',
                        'name' => 'name',
                        'localName' => 'name',
                    ],
                    [
                        'listName' => 'descriptions',
                        'name' => 'description',
                        'localName' => 'description',
                    ]
                ]);
            });
        });
    }

    public function seedGameVersions()
    {
        $this->getClass('version-group', function ($url) {
            $this->getObject($url, function ($version){
                $gen = $this->romanToInt(str_replace('generation-', '', $version->generation->name));

                $localVersion = \App\Models\GameVersion::updateOrCreate([
                    'id' => $version->id,
                ], [
                    'generic_name' => $version->name,
                    'generation' => $gen,
                ]);

                $names = [];

                foreach ($version->versions as $game) {
                    $version = $this->request($game->url);
                    foreach ($version->names as $name) {
                        $langKey = $this->translationsBridge[$name->language->name];

                        if (isset($names[$langKey])) {
                            $names[$langKey].= ' / ' . $name->name;
                        }else{
                            $names[$langKey] = $name->name;
                        }
                    }
                }

                foreach ($names as $lang => $name) {
                    $localVersion->translateOrNew($lang)->name = $name;
                }
                $localVersion->save();
            });
        });
    }

    public function seedAbilities()
    {
        $this->getClass('ability', function ($url) {
            $this->getObject($url, function ($ability){
                $localAbility = \App\Models\Ability::updateOrCreate([
                    'id' => $ability->id,
                ], []);

                $this->saveTranslations($localAbility, $ability, [
                    [
                        'listName' => 'names',
                        'name' => 'name',
                        'localName' => 'name',
                    ],
                    [
                        'listName' => 'flavor_text_entries',
                        'name' => 'flavor_text',
                        'localName' => 'description',
                    ],
                    [
                        'listName' => 'effect_entries',
                        'name' => 'effect',
                        'localName' => 'effect',
                    ],
                ]);
            });
        });
    }

    public function seedEvolutionTrigger()
    {
        $this->getClass('evolution-trigger', function ($url) {
            $this->getObject($url, function ($evolutionTrigger){
                $localEvolutionTrigger = \App\Models\EvolutionTrigger::updateOrCreate([
                    'id' => $evolutionTrigger->id,
                    'slug' => $evolutionTrigger->name,
                ], []);

                $this->saveTranslations($localEvolutionTrigger, $evolutionTrigger, [
                    [
                        'listName' => 'names',
                        'name' => 'name',
                        'localName' => 'name',
                    ],
                ]);
            });
        });
    }

    public function seedItems()
    {
        $this->getClass('item', function ($url) {
            $this->getObject($url, function ($item){
                $localItem = \App\Models\Item::updateOrCreate([
                    'id' => $item->id,
                ], [
                    'sprite_url' => $item->sprites->default,
                ]);

                $this->saveTranslations($localItem, $item, [
                    [
                        'listName' => 'names',
                        'name' => 'name',
                        'localName' => 'name',
                    ],
                    [
                        'listName' => 'flavor_text_entries',
                        'name' => 'flavor_text',
                        'localName' => 'description',
                    ],
                ]);
            });
        });
    }

    public function seedPokemon(){
        $this->getClass('pokemon-species', function ($url) {
            $this->getObject($url, function ($pokemon){
                $localPokemon = Pokemon::updateOrCreate([
                    'id' => $pokemon->pokedex_numbers[0]->entry_number,
                ], [
                    'has_gender_differences' => $pokemon->has_gender_differences,
                    'is_baby' => $pokemon->is_baby,
                    'is_legendary' => $pokemon->is_legendary,
                    'is_mythical' => $pokemon->is_mythical,
                ]);

                $this->saveTranslations($localPokemon, $pokemon, [
                    [
                        'listName' => 'names',
                        'name' => 'name',
                        'localName' => 'name',
                    ],
                    [
                        'listName' => 'genera',
                        'name' => 'genus',
                        'localName' => 'category',
                    ],
                ]);

                foreach ($pokemon->varieties as $variety) {
                    $this->command->info('      🟣 Fetching ' . $variety->pokemon->name . '...');

                    $pokemonVariety = $this->request($variety->pokemon->url);

                    foreach ($pokemonVariety->forms as $form) {
                        //$this->command->info('          🟢 Fetching ' . $form->name . '...');
                        $pokemonVarietyForm = $this->request($form->url);

                        $localPokemonVariety = \App\Models\PokemonVariety::updateOrCreate([
                            'id' => $pokemonVarietyForm->id,
                        ], [
                            'pokemon_id' => $localPokemon->id,
                            'is_default' => $pokemonVariety->is_default,
                            'cry_url' => $pokemonVariety->cries->latest,
                            'height' => $pokemonVariety->height,
                            'weight' => $pokemonVariety->weight,
                            'base_experience' => $pokemonVariety->base_experience,
                            'base_stat_hp' => $this->getBaseStat($pokemonVariety->stats, 'hp'),
                            'base_stat_attack' => $this->getBaseStat($pokemonVariety->stats, 'attack'),
                            'base_stat_defense' => $this->getBaseStat($pokemonVariety->stats, 'defense'),
                            'base_stat_special_attack' => $this->getBaseStat($pokemonVariety->stats, 'special-attack'),
                            'base_stat_special_defense' => $this->getBaseStat($pokemonVariety->stats, 'special-defense'),
                            'base_stat_speed' => $this->getBaseStat($pokemonVariety->stats, 'speed'),
                        ]);

                        $translations = [];

                        foreach ($pokemon->flavor_text_entries as $flavorText) {
                            $langKey = $this->translationsBridge[$flavorText->language->name] ?? null;
                            if ($langKey !== null) {
                                if (!isset($translations[$langKey])) {
                                    $translations[$langKey] = [];
                                }
                                $translations[$langKey]['description'] = $flavorText->flavor_text;
                            }
                        }
                        foreach ($pokemonVarietyForm->names as $name) {
                            $langKey = $this->translationsBridge[$name->language->name] ?? null;
                            if ($langKey !== null) {
                                if (!isset($translations[$langKey])) {
                                    $translations[$langKey] = [];
                                }
                                $translations[$langKey]['name'] = $name->name;
                            }
                        }
                        foreach ($pokemonVarietyForm->form_names as $formName) {
                            $langKey = $this->translationsBridge[$formName->language->name] ?? null;
                            if ($langKey !== null) {
                                if (!isset($translations[$langKey])) {
                                    $translations[$langKey] = [];
                                }
                                $translations[$langKey]['form_name'] = $formName->name;
                            }
                        }

                        foreach ($translations as $locale => $fields) {
                            foreach ($fields as $field => $value) {
                                $localPokemonVariety->translateOrNew($locale)->{$field} = $value;
                            }
                        }

                        $localPokemonVariety->save();

                        try {
                            PokemonVarietySprite::updateOrCreate([
                                'pokemon_variety_id' => $localPokemonVariety->id,
                            ], [
                                'artwork_url' => $pokemonVariety->sprites->other->{'official-artwork'}->front_default,
                                'artwork_shiny_url' => $pokemonVariety->sprites->other->{'official-artwork'}->front_shiny,
                                'front_url' => $pokemonVarietyForm->sprites->front_default ?? $pokemonVariety->sprites->front_default,
                                'front_female_url' => $pokemonVarietyForm->sprites->front_female,
                                'front_shiny_url' => $pokemonVarietyForm->sprites->front_shiny,
                                'front_shiny_female_url' => $pokemonVarietyForm->sprites->front_shiny_female,
                                'back_url' => $pokemonVarietyForm->sprites->back_default,
                                'back_female_url' => $pokemonVarietyForm->sprites->back_female,
                                'back_shiny_url' => $pokemonVarietyForm->sprites->back_shiny,
                                'back_shiny_female_url' => $pokemonVarietyForm->sprites->back_shiny_female,
                            ]);
                        }catch (\Exception $e){
                            $this->command->warn($e->getMessage());
                        }


                        foreach ($pokemonVarietyForm->types as $type) {
                            $localType = \App\Models\Type::whereTranslation('name', $type->type->name)->first();
                            if ($localType) {
                                $localPokemonVariety->types()->attach($localType->id, [
                                    'slot' => $type->slot,
                                ]);
                            }
                        }


                        foreach ($pokemonVariety->abilities as $ability) {
                            $localAbility = \App\Models\Ability::whereTranslation('name', $ability->ability->name)->first();
                            if ($localAbility) {
                                try {
                                    $localPokemonVariety->abilities()->attach($localAbility->id, [
                                        'is_hidden' => $ability->is_hidden,
                                        'slot' => $ability->slot,
                                    ]);
                                } catch (\Exception $e) {
                                    $this->command->warn($e->getMessage());
                                }
                            }
                        }

                        foreach ($pokemonVariety->moves as $move){
                            $localMove = \App\Models\Move::whereTranslation('name', $move->move->name)->first();

                            foreach ($move->version_group_details as $versionGroupDetail) {
                                $learnMethod = \App\Models\MoveLearnMethod::whereTranslation('name', $versionGroupDetail->move_learn_method->name)->first();
                                $version = \App\Models\GameVersion::where('generic_name', $versionGroupDetail->version_group->name)->first();

                                if ($localMove && $learnMethod && $version) {
                                    PokemonLearnMove::updateOrCreate([
                                        'pokemon_variety_id' => $localPokemonVariety->id,
                                        'move_id' => $localMove->id,
                                        'game_version_id' => $version->id,
                                        'move_learn_method_id' => $learnMethod->id,
                                    ], [
                                        'level' => $versionGroupDetail->level_learned_at,
                                    ]);
                                }
                            }
                        }
                    }
                }
            });
        });
    }

    public function seedEvolutions()
    {
        $this->getClass('evolution-chain', function ($url) {
            $this->getObject($url, function ($evolutionChain){

                // BOUCLE RÉCURSIVE DE SES GRANDS MORTS POUR RÉCUPÉRER TOUTES LES ÉVOLUTIONS

                $evolutionFn = function ($chain) use (&$evolutionFn) {
                    if (count($chain->evolves_to) > 0){
                        foreach ($chain->evolves_to as $evolution) {
                            $trigger = \App\Models\EvolutionTrigger::where('slug', $evolution->evolution_details[0]->trigger->name)->first();

                            $fromPokemon = Pokemon::whereTranslation('name', $chain->species->name)->first();
                            $toPokemon = Pokemon::whereTranslation('name', $evolution->species->name)->first();

                            if (!$fromPokemon){
                                $pokemonFromAPI = $this->requestPokeApi('pokemon/' . $chain->species->name);

                                $fromPokemon = Pokemon::where('id', $pokemonFromAPI->id)->first();

                                if (!$fromPokemon){
                                    return;
                                }
                            }

                            if (!$toPokemon){
                                $pokemonToAPI = $this->requestPokeApi('pokemon/' . $evolution->species->name);

                                $toPokemon = Pokemon::where('id', $pokemonToAPI->id)->first();

                                if (!$toPokemon){
                                    return;
                                }
                            }

                            foreach ($fromPokemon->varieties()->get() as $fromVariety){
                                $toVariety = null;

                                if ($fromVariety->is_default === true){
                                    $toVariety = $toPokemon->defaultVariety()->first();

                                }elseif ($fromVariety->form_name){
                                    $toVariety = $toPokemon->varieties()->whereTranslation('form_name', $fromVariety->form_name)->first();
                                }

                                if ($toVariety && $trigger && count($evolution->evolution_details) > 0){
                                    // if ($fromVariety->form_name){
                                    //     $this->command->info('      🟢 Fetching ' . $fromPokemon->name . ' ' . $fromVariety->form_name . ' to ' . $toPokemon->name . ' ' . $toVariety->form_name . '...');
                                    // }else{
                                    //     $this->command->info('      🟢 Fetching ' . $fromPokemon->name . ' to ' . $toPokemon->name . '...');
                                    // }

                                    $evolutionDetails = $evolution->evolution_details[0] ?? null;

                                    try{
                                        // Maintenant, utilisons cette approche dans notre updateOrCreate
                                        PokemonEvolution::updateOrCreate([
                                            'pokemon_variety_id' => $fromVariety->id,
                                            'evolves_to_id' => $toVariety->id,
                                        ], [
                                            'gender' => $evolutionDetails->gender ?? null,
                                            'held_item_id' => $this->safelyGetId(Item::class, 'name', $evolutionDetails->held_item->name ?? null),
                                            'item_id' => $this->safelyGetId(Item::class, 'name', $evolutionDetails->item->name ?? null),
                                            'known_move_id' => $this->safelyGetId(\App\Models\Move::class, 'name', $evolutionDetails->known_move->name ?? null),
                                            'known_move_type_id' => $this->safelyGetId(\App\Models\Type::class, 'name', $evolutionDetails->known_move_type->name ?? null),
                                            'location' => $evolutionDetails->location->name ?? null,
                                            'min_affection' => $evolutionDetails->min_affection ?? null,
                                            'min_happiness' => $evolutionDetails->min_happiness ?? null,
                                            'min_level' => $evolutionDetails->min_level ?? null,
                                            'needs_overworld_rain' => $evolutionDetails->needs_overworld_rain ?? null,
                                            'party_species_id' => $this->safelyGetId(Pokemon::class, 'name', $evolutionDetails->party_species->name ?? null),
                                            'party_type_id' => $this->safelyGetId(\App\Models\Type::class, 'name', $evolutionDetails->party_type->name ?? null),
                                            'relative_physical_stats' => $evolutionDetails->relative_physical_stats ?? null,
                                            'time_of_day' => $evolutionDetails->time_of_day ?? null,
                                            'trade_species_id' => $this->safelyGetId(Pokemon::class, 'name', $evolutionDetails->trade_species->name ?? null),
                                            'turn_upside_down' => $evolutionDetails->turn_upside_down ?? null,
                                            'evolution_trigger_id' => $trigger->id,
                                        ]);
                                    }catch (\Exception $e){
                                        $this->command->warn($e->getMessage());
                                        $this->command->warn($e->getTraceAsString());
                                    }

                                }
                            }

                            $evolutionFn($evolution);
                        }
                    }
                };

                $evolutionFn($evolutionChain->chain);
            });
        });
    }
}