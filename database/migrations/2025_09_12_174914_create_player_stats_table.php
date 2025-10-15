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
        Schema::create('player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('points');
            $table->unsignedSmallInteger('rebounds');
            $table->unsignedSmallInteger('assists');
            $table->unsignedSmallInteger('steals');
            $table->unsignedSmallInteger('blocks');
            $table->unsignedSmallInteger('fg_made');
            $table->unsignedSmallInteger('fg_attempts');
            $table->unsignedSmallInteger('three_made');
            $table->unsignedSmallInteger('three_attempts');
            $table->unsignedSmallInteger('minutes_played');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_stats');
    }
};
