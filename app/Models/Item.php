<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Item extends Model implements TranslatableContract
{
    use HasFactory, Translatable;

    public $translatedAttributes = ['name','description'];

    protected $fillable=['sprite_url'];

    public function heldItemId(){
        return $this->hasMany(PokemonEvolution::class, 'held_item_id');
    }

    public function ItemId(){
        return $this->hasMany(PokemonEvolution::class, 'item_id');
    }
}
