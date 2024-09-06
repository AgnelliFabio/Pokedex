<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class Pokemon extends Model implements TranslatableContract
{
  use HasFactory, Translatable;

  // Liste des attributs traduits
  public $translatedAttributes = ['name', 'category'];

  // Liste des attributs autorisés pour la création et la mise à jour des données de manière massive
  protected $fillable = ['has_gender_differences', 'is_baby', 'is_legendary', 'is_mythical'];

  // Définition des types de données des attributs
  protected $casts = [
    'has_gender_differences' => 'boolean',
    'is_baby' => 'boolean',
    'is_legendary' => 'boolean',
    'is_mythical' => 'boolean',
  ];

  public function varieties()
  {
    return $this->hasMany(PokemonVariety::class);
  }

  public function defaultVariety()
  {
    return $this->hasOne(PokemonVariety::class)
      ->where('is_default', true);
  }
  public function catchByUsers()
  {
    return $this->belongsToMany(User::class);
  }
}
