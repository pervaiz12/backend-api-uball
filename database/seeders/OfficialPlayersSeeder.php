<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OfficialPlayersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $officialPlayers = [
            [
                'name' => 'LeBron James',
                'email' => 'lebron@uball.com',
                'city' => 'Los Angeles',
                'home_court' => 'Crypto.com Arena'
            ],
            [
                'name' => 'Stephen Curry',
                'email' => 'curry@uball.com',
                'city' => 'San Francisco',
                'home_court' => 'Chase Center'
            ],
            [
                'name' => 'Kevin Durant',
                'email' => 'kd@uball.com',
                'city' => 'Phoenix',
                'home_court' => 'Footprint Center'
            ],
            [
                'name' => 'Giannis Antetokounmpo',
                'email' => 'giannis@uball.com',
                'city' => 'Milwaukee',
                'home_court' => 'Fiserv Forum'
            ],
            [
                'name' => 'Luka Dončić',
                'email' => 'luka@uball.com',
                'city' => 'Dallas',
                'home_court' => 'American Airlines Center'
            ],
            [
                'name' => 'Jayson Tatum',
                'email' => 'tatum@uball.com',
                'city' => 'Boston',
                'home_court' => 'TD Garden'
            ],
            [
                'name' => 'Joel Embiid',
                'email' => 'embiid@uball.com',
                'city' => 'Philadelphia',
                'home_court' => 'Wells Fargo Center'
            ],
            [
                'name' => 'Nikola Jokić',
                'email' => 'jokic@uball.com',
                'city' => 'Denver',
                'home_court' => 'Ball Arena'
            ],
        ];

        foreach ($officialPlayers as $playerData) {
            User::create([
                'name' => $playerData['name'],
                'email' => $playerData['email'],
                'password' => Hash::make('password123'),
                'role' => 'player',
                'is_official' => true,
                'city' => $playerData['city'],
                'home_court' => $playerData['home_court'],
            ]);
        }

        $this->command->info('Official players seeded successfully!');
    }
}
