<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

use function PHPSTORM_META\type;

class Move extends Model implements TranslatableContract
{
    use HasFactory, Translatable;

    public $translatedAttributes = ['name','description'];

    protected $fillable=['accuracy','power','pp','priority'];

    public function pokemonEvolution(){
        return $this->hasMany(PokemonEvolution::class);
    }

    public function pokemonLearnMove(){
        return $this->hasMany(PokemonLearnMove::class);
    }

    public function damageClass(){
        return $this->belongsTo(MoveDamageClass::class);
    }

    public function types(){
        return $this->belongsTo(Type::class);
    }
}
