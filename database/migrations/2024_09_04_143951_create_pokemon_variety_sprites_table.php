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
        Schema::create('pokemon_variety_sprites', function (Blueprint $table) {
            $table->id();
            $table->ForeignIdFor(App\Models\PokemonVariety::class)->constrained()->onDelete('cascade');
            $table->text('artwork_url');
            $table->text('artwork_shiny_url')->nullable();
            $table->text('front_url')->nullable();
            $table->text('front_female_url')->nullable();
            $table->text('front_shiny_url')->nullable();
            $table->text('front_shiny_female_url')->nullable();
            $table->text('back_url')->nullable();
            $table->text('back_female_url')->nullable();
            $table->text('back_shiny_url')->nullable();
            $table->text('back_shiny_female_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemon_variety_sprites');
    }
};
