<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;

class PostsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'player')->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('No players found. Please seed users first.');
            return;
        }

        $samplePosts = [
            [
                'content' => 'Just dropped 30 points in tonight\'s game! 🔥 The team played amazing and we secured the W!',
                'media_url' => '/clips/highlight-1.mp4',
                'media_type' => 'video',
                'badge' => 'DUNK',
                'likes_count' => 45,
                'comments_count' => 12,
                'shares_count' => 8,
                'is_highlight' => true,
            ],
            [
                'content' => 'Practice makes perfect! Working on my three-point shot every day 💪',
                'media_url' => '/clips/practice-1.mp4',
                'media_type' => 'video',
                'badge' => '3PT',
                'likes_count' => 23,
                'comments_count' => 5,
                'shares_count' => 3,
                'is_highlight' => false,
            ],
            [
                'content' => 'Amazing game tonight! Big thanks to my teammates for the assists 🙌',
                'media_url' => '/clips/team-play.mp4',
                'media_type' => 'video',
                'badge' => 'ASSIST',
                'likes_count' => 67,
                'comments_count' => 18,
                'shares_count' => 12,
                'is_highlight' => true,
            ],
            [
                'content' => 'New season, new goals! Ready to take it to the next level 🏀',
                'media_url' => null,
                'media_type' => 'text',
                'badge' => null,
                'likes_count' => 34,
                'comments_count' => 8,
                'shares_count' => 5,
                'is_highlight' => false,
            ],
            [
                'content' => 'That crossover though! 😤 Defense couldn\'t keep up',
                'media_url' => '/clips/crossover.mp4',
                'media_type' => 'video',
                'badge' => 'CROSSOVER',
                'likes_count' => 89,
                'comments_count' => 25,
                'shares_count' => 15,
                'is_highlight' => true,
            ],
            [
                'content' => 'Training with the squad! Chemistry is everything in basketball 🔥',
                'media_url' => '/images/team-training.jpg',
                'media_type' => 'image',
                'badge' => 'TEAM',
                'likes_count' => 42,
                'comments_count' => 9,
                'shares_count' => 6,
                'is_highlight' => false,
            ],
        ];

        foreach ($samplePosts as $postData) {
            $randomUser = $users->random();
            
            Post::create([
                'user_id' => $randomUser->id,
                ...$postData,
                'created_at' => now()->subDays(rand(0, 7))->subHours(rand(0, 23)),
            ]);
        }

        $this->command->info('Sample posts seeded successfully!');
    }
}
