<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class MoveDamageClass extends Model implements TranslatableContract   
{
    use HasFactory, Translatable;

    public $translatedAttributes = ['name','description'];

    public function moves(){
        return $this->hasMany(Move::class);
    }
}
