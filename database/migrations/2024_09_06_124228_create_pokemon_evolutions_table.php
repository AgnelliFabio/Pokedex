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
        Schema::create('pokemon_evolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\PokemonVariety::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(\App\Models\PokemonVariety::class, "evolves_to_id")->constrained('pokemon_varieties')->onDelete('cascade');
            $table->boolean("gender")->nullable();
            $table->foreignIdFor(\App\Models\Item::class, "held_item_id")->nullable()->constrained('items')->onDelete('cascade');
            $table->foreignIdFor(\App\Models\Item::class)->nullable()->constrained()->onDelete('cascade');
            $table->foreignIdFor(\App\Models\Move::class, "known_move_id")->nullable()->constrained('moves')->onDelete('cascade');
            $table->foreignIdFor(\App\Models\Type::class, "known_move_type_id")->nullable()->constrained('types')->onDelete('cascade');
            $table->string("location")->nullable();
            $table->integer("min_affection")->nullable();
            $table->integer("min_happiness")->nullable();
            $table->integer("min_level")->nullable();
            $table->boolean("needs_overworld_rain")->default(false);
            $table->foreignIdFor(\App\Models\Pokemon::class, "party_species_id")->nullable()->constrained('pokemon')->onDelete('cascade');
            $table->foreignIdFor(\App\Models\Type::class, "party_type_id")->nullable()->constrained('types')->onDelete('cascade');
            $table->integer("relative_physical_stats")->nullable();
            $table->string("time_of_day")->nullable();
            $table->foreignIdFor(\App\Models\Pokemon::class, "trade_species_id")->nullable()->constrained('pokemon')->onDelete('cascade');
            $table->boolean("turn_upside_down")->default(false);
            $table->foreignIdFor(\App\Models\EvolutionTrigger::class)->nullable()->constrained('evolution_triggers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemon_evolutions');
    }
};
