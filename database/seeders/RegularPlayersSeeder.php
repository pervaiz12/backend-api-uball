<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegularPlayersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regularPlayers = [
            [
                'name' => 'Jonny Daniel',
                'email' => 'jonny@uball.com',
                'city' => 'New York',
                'home_court' => 'Madison Square Garden'
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike@uball.com',
                'city' => 'Chicago',
                'home_court' => 'United Center'
            ],
            [
                'name' => 'Alex Rodriguez',
                'email' => 'alex@uball.com',
                'city' => 'Miami',
                'home_court' => 'FTX Arena'
            ],
            [
                'name' => 'Chris Williams',
                'email' => 'chris@uball.com',
                'city' => 'Atlanta',
                'home_court' => 'State Farm Arena'
            ],
            [
                'name' => 'David Brown',
                'email' => 'david@uball.com',
                'city' => 'Houston',
                'home_court' => 'Toyota Center'
            ],
            [
                'name' => 'Marcus Thompson',
                'email' => 'marcus@uball.com',
                'city' => 'Detroit',
                'home_court' => 'Little Caesars Arena'
            ],
        ];

        foreach ($regularPlayers as $playerData) {
            User::create([
                'name' => $playerData['name'],
                'email' => $playerData['email'],
                'password' => Hash::make('password123'),
                'role' => 'player',
                'is_official' => false,
                'city' => $playerData['city'],
                'home_court' => $playerData['home_court'],
            ]);
        }

        $this->command->info('Regular players seeded successfully!');
    }
}
