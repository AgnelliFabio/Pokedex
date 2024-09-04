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
        Schema::create('pokemon_variety_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ForeignIdFor(App\Models\PokemonVariety::class)->constrained()->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('name')->nullable();
            $table->string('form_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['pokemon_variety_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemon_variety_translations');
    }
};
