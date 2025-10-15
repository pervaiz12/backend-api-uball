<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;

class MorePostsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'player')->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        $samplePosts = [
            [
                'content' => 'Just finished an intense training session! 💪 Ready for the championship game this weekend.',
                'media_url' => '/clips/training-session.mp4',
                'media_type' => 'video',
                'badge' => 'TRAINING',
                'is_highlight' => false,
            ],
            [
                'content' => 'Game winner from half court! 🎯 Sometimes you just gotta shoot your shot!',
                'media_url' => '/clips/half-court-shot.mp4',
                'media_type' => 'video',
                'badge' => '3PT',
                'is_highlight' => true,
            ],
            [
                'content' => 'Team chemistry is everything! Great win tonight with my squad 🏆',
                'media_url' => '/images/team-celebration.jpg',
                'media_type' => 'image',
                'badge' => 'TEAM',
                'is_highlight' => false,
            ],
            [
                'content' => 'Working on my handles every single day. The grind never stops! 🔥',
                'media_url' => '/clips/dribbling-practice.mp4',
                'media_type' => 'video',
                'badge' => 'CROSSOVER',
                'is_highlight' => false,
            ],
            [
                'content' => 'Posterized! 😤 Sometimes you gotta remind them who runs the paint.',
                'media_url' => '/clips/poster-dunk.mp4',
                'media_type' => 'video',
                'badge' => 'DUNK',
                'is_highlight' => true,
            ],
            [
                'content' => 'Perfect pass for the easy bucket! Vision is everything in basketball 👁️',
                'media_url' => '/clips/no-look-pass.mp4',
                'media_type' => 'video',
                'badge' => 'ASSIST',
                'is_highlight' => false,
            ],
            [
                'content' => 'New season, new goals! Time to take my game to the next level 📈',
                'media_url' => null,
                'media_type' => 'text',
                'badge' => null,
                'is_highlight' => false,
            ],
            [
                'content' => 'Clutch free throws to seal the game! Ice in my veins ❄️',
                'media_url' => '/clips/clutch-free-throws.mp4',
                'media_type' => 'video',
                'badge' => 'CLUTCH',
                'is_highlight' => true,
            ],
            [
                'content' => 'Young players showing out at the local court! The future is bright 🌟',
                'media_url' => '/images/young-players.jpg',
                'media_type' => 'image',
                'badge' => 'COMMUNITY',
                'is_highlight' => false,
            ],
            [
                'content' => 'Behind the back, through the legs, and finish! 🎭 Style points matter!',
                'media_url' => '/clips/fancy-layup.mp4',
                'media_type' => 'video',
                'badge' => 'STYLE',
                'is_highlight' => true,
            ],
        ];

        foreach ($samplePosts as $postData) {
            $randomUser = $users->random();
            
            Post::create([
                'user_id' => $randomUser->id,
                'content' => $postData['content'],
                'media_url' => $postData['media_url'],
                'media_type' => $postData['media_type'],
                'badge' => $postData['badge'],
                'likes_count' => rand(15, 120),
                'comments_count' => rand(2, 25),
                'shares_count' => rand(1, 15),
                'is_highlight' => $postData['is_highlight'],
                'created_at' => now()->subHours(rand(1, 168)), // Random time within last week
            ]);
        }

        $this->command->info('Additional posts seeded successfully!');
    }
}
