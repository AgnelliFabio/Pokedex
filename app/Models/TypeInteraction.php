<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeInteraction extends Model
{
    use HasFactory;

    public $fillable = ['from_type_id', 'to_type_id', 'type_interaction_id'];

    public function typeInteractionState(){
        return $this->belongsTo(TypeInteractionState::class, 'type_interaction_state_id');
    }
}
