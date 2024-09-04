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
        Schema::create('pokemon_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ForeignIdFor(App\Models\Pokemon::class)->constrained()->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('name');
            $table->string('category')->nullable();
            $table->timestamps();

            $table->unique(['pokemon_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemon_translations');
    }
};
