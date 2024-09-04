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
        Schema::table('users', function (Blueprint $table) {
            $table->string('github_id')->after('id');
            $table->after('email_verified_at', function ($table) {
                $table->text('profil_picture_url')->nullable();
                $table->string('locale')->default('fr');
                $table->string('theme')->default('light');
            });

            $table->dropColumn('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('github_id');
            $table->dropColumn('profil_picture_url');
            $table->dropColumn('locale');
            $table->dropColumn('theme');

            $table->string('password');
        });
    }
};
