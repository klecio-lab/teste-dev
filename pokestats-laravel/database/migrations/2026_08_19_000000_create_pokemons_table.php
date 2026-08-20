<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokemons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id')->unique();
            $table->string('name', 100)->index();
            $table->unsignedSmallInteger('height');
            $table->unsignedSmallInteger('weight');
            $table->unsignedSmallInteger('base_experience')->nullable();
            $table->unsignedSmallInteger('hp')->index();
            $table->unsignedSmallInteger('attack')->index();
            $table->unsignedSmallInteger('defense')->index();
            $table->unsignedSmallInteger('special_attack')->index();
            $table->unsignedSmallInteger('special_defense')->index();
            $table->unsignedSmallInteger('speed')->index();
            $table->string('sprite_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemons');
    }
};
