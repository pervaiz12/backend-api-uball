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
            $table->decimal('avg_points', 4, 1)->default(0);
            $table->decimal('avg_rebounds', 4, 1)->default(0);
            $table->decimal('avg_assists', 4, 1)->default(0);
            $table->decimal('field_goal_percentage', 5, 2)->default(0);
            $table->integer('total_games_played')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avg_points', 'avg_rebounds', 'avg_assists', 'field_goal_percentage', 'total_games_played']);
        });
    }
};
