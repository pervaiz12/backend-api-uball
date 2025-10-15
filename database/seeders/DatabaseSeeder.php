<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Game;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@uball.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'can_upload' => true,
                'is_official' => true,
                'email_verified_at' => now(),
            ]
        );

        // Staff user
        $staff = User::firstOrCreate(
            ['email' => 'staff@uball.com'],
            [
                'name' => 'Staff',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );

        // Sample game
        Game::firstOrCreate(
            [
                'location' => 'Court A',
                'game_date' => now()->addDays(1),
                'created_by' => $staff->id,
            ]
        );
    }
}
