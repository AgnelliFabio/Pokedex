<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Type extends Model implements TranslatableContract
{
    use HasFactory, Translatable;

    public $translatedAttributes = ['name'];

    protected $fillable = ['sprite_url'];

    public function knowMoveTypeId()
    {
        return $this->hasMany(PokemonEvolution::class, 'know_move_type_id');
    }

    public function partyTypeId()
    {
        return $this->hasMany(PokemonEvolution::class, 'party_type_id');
    }

    public function interactTo()
    {
        $this->belongsToMany(Type::class, 'type_interaction', 'from_type_id', 'to_type_id')
            ->withPivot('type_interaction_state_id');
    }

    public function interactedBy()
    {
        return $this->belongsToMany(Type::class, 'type_interactions', 'to_type_id', 'from_type_id')
            ->withPivot('type_interaction_state_id');
    }

    public function pokemonVarietyType()
    {
        return $this->belongsToMany(PokemonVariety::class);
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
