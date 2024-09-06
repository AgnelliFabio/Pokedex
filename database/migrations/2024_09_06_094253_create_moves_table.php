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
        Schema::create('moves', function (Blueprint $table) {
            $table->id();
            $table->integer('accuracy')->nullable();
            $table->foreignIdFor(\App\Models\MoveDamageClass::class)->constrained()->onDelete('cascade');
            $table->integer('power')->nullable();
            $table->integer('pp');
            $table->integer('priority');
            $table->foreignIdFor(\App\Models\Type::class)->constrained()->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moves');
    }
};
