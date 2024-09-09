<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PokemonLearnMove extends Model
{
    use HasFactory;

    protected $fillable = ['level'];

    public function pokemonVariety(){
        return $this->belongsTo(pokemonVariety::class);
    }

    public function move(){
        return $this->belongsTo(Move::class);
    }

    public function moveLearnMethod(){
        return $this->belongsTo(moveLearnMethod::class);
    }

    public function gameVersion(){
        return $this->belongsTo(gameVersion::class);
    }
}
