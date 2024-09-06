<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoveDamageClassTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['name','description'];
}
