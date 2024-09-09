<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ability_pokemon_variety', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(\App\Models\Ability::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(\App\Models\PokemonVariety::class)->constrained()->onDelete('cascade');
            $table->boolean("is_hidden")->default(false);
            $table->integer("slot");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ability_pokemon_variety');
    }
};
