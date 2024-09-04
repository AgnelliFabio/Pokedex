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
        Schema::create('pokemon_varieties', function (Blueprint $table) {
            $table->id();
            $table->ForeignIdFor(App\Models\Pokemon::class)->constrained()->onDelete('cascade');
            $table->boolean('is_default')->default(false);
            $table->text('cry_url')->nullable();
            $table->integer('height');
            $table->integer('weight');
            $table->integer('base_experience')->nullable();
            $table->integer('base_stat_hp');
            $table->integer('base_stat_attack');
            $table->integer('base_stat_defense');
            $table->integer('base_stat_special_attack');
            $table->integer('base_stat_special_defense');
            $table->integer('base_stat_speed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemon_varieties');
    }
};
