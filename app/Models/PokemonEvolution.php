<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use function PHPSTORM_META\type;

class PokemonEvolution extends Model
{
    use HasFactory;

    public function varietyId(){
        return $this->belongsTo(pokemonVariety::class, 'pokemon_variety_id');
    }

    public function evolvesToPokemonId(){
        return $this->belongsTo(pokemonVariety::class, 'evolves_to_id');
    }

    public function heldItemId(){
        return $this->belongsTo(Item::class, 'held_item_id');
    }

    public function itemId(){
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function moves(){
        return $this->belongsTo(Move::class);
    }

    public function knowMoveTypeId(){
        return $this->belongsTo(Type::class, 'know_move_type_id');
    }

    public function partyTypeId(){
        return $this->belongsTo(Type::class, 'party_type_id');
    }

    public function partySpeciesId(){
        return $this->belongsTo(Pokemon::class, 'party_species_id');
    }

    public function tradeSpeciesId(){
        return $this->belongsTo(Pokemon::class, 'trade_species_id');
    }

    public function evolutionTrigger(){
        return $this->belongsTo(evolutionTrigger::class);
    }
}
