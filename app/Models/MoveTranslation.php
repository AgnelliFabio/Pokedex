<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoveTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['name','description'];
}
