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
        Schema::table('clips', function (Blueprint $table) {
            // Tags/Categories
            $table->json('tags')->nullable()->after('description');
            
            // Video URL alternative (for YouTube/Vimeo links)
            $table->string('external_video_url')->nullable()->after('video_url');
            
            // Thumbnail/Cover image
            $table->string('thumbnail_url')->nullable()->after('external_video_url');
            
            // Game Result Information
            $table->string('team_name')->nullable()->after('thumbnail_url');
            $table->string('opponent_team')->nullable()->after('team_name');
            $table->enum('game_result', ['win', 'loss'])->nullable()->after('opponent_team');
            $table->integer('team_score')->nullable()->after('game_result');
            $table->integer('opponent_score')->nullable()->after('team_score');
            
            // Calculated percentages (stored for performance)
            $table->decimal('fg_percentage', 5, 2)->nullable()->after('opponent_score');
            $table->decimal('three_pt_percentage', 5, 2)->nullable()->after('fg_percentage');
            $table->decimal('four_pt_percentage', 5, 2)->nullable()->after('three_pt_percentage');
            
            // Visibility and Display Options
            $table->enum('visibility', ['public', 'private', 'pending'])->default('public')->after('status');
            $table->boolean('show_in_trending')->default(false)->after('visibility');
            $table->boolean('show_in_profile')->default(true)->after('show_in_trending');
            $table->boolean('feature_on_dashboard')->default(false)->after('show_in_profile');
            
            // Season/Year
            $table->string('season', 4)->default('2024')->after('feature_on_dashboard');
            
            // Additional metadata
            $table->string('title')->nullable()->after('season');
            $table->integer('likes_count')->default(0)->after('title');
            $table->integer('comments_count')->default(0)->after('likes_count');
            $table->integer('views_count')->default(0)->after('comments_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clips', function (Blueprint $table) {
            $table->dropColumn([
                'tags',
                'external_video_url',
                'thumbnail_url',
                'team_name',
                'opponent_team',
                'game_result',
                'team_score',
                'opponent_score',
                'fg_percentage',
                'three_pt_percentage',
                'four_pt_percentage',
                'visibility',
                'show_in_trending',
                'show_in_profile',
                'feature_on_dashboard',
                'season',
                'title',
                'likes_count',
                'comments_count',
                'views_count'
            ]);
        });
    }
};
