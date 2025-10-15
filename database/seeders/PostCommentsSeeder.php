<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PostComment;
use App\Models\Post;
use App\Models\User;

class PostCommentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = Post::all();
        $users = User::where('role', 'player')->get();
        
        if ($posts->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No posts or users found. Please seed posts and users first.');
            return;
        }

        $sampleComments = [
            'Amazing play! Keep it up! 🔥',
            'That was insane! 🏀',
            'Incredible skills bro!',
            'This is why you\'re the GOAT! 🐐',
            'Teach me that move!',
            'Absolutely destroyed them! 💪',
            'Clean shot! Nothing but net 🎯',
            'That crossover was nasty! 😤',
            'Best highlight I\'ve seen today!',
            'You make it look so easy!',
            'Respect! 👏',
            'This deserves more views!',
        ];

        foreach ($posts as $post) {
            // Add 2-4 random comments per post
            $commentCount = rand(2, 4);
            
            for ($i = 0; $i < $commentCount; $i++) {
                $randomUser = $users->random();
                $randomComment = $sampleComments[array_rand($sampleComments)];
                
                PostComment::create([
                    'user_id' => $randomUser->id,
                    'post_id' => $post->id,
                    'content' => $randomComment,
                    'created_at' => now()->subHours(rand(1, 48)),
                ]);
            }
            
            // Update the post's comment count
            $post->update(['comments_count' => $commentCount]);
        }

        $this->command->info('Sample post comments seeded successfully!');
    }
}
