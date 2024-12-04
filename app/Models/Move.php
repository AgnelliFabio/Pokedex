<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Move extends Model implements TranslatableContract
{
    use HasFactory, Translatable;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = ['accuracy', 'power', 'pp', 'priority'];

    public function pokemonEvolution()
    {
        return $this->hasMany(PokemonEvolution::class);
    }

    public function pokemonLearnMove()
    {
        return $this->hasMany(PokemonLearnMove::class);
    }

    public function moveDamageClass()
    {
        return $this->belongsTo(MoveDamageClass::class, 'move_damage_class_id');
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }
}
