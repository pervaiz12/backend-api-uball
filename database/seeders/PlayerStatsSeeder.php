<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class PlayerStatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'player')->get();
        
        // Get the first official player as the game creator
        $firstPlayer = $officialPlayers->first();
        if (!$firstPlayer) {
            $this->command->error('No official players found. Please run OfficialPlayersSeeder first.');
            return;
        }

        for ($i = 1; $i <= 10; $i++) {
            $gameId = DB::table('games')->insertGetId([
                'location' => 'Court ' . $i . ' - Basketball Arena',
                'game_date' => now()->subDays(rand(1, 30)),
                'created_by' => $firstPlayer->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $gameIds[] = $gameId;
        }

        // Create player stats for each official player
        foreach ($officialPlayers as $player) {
            // Create 3-8 games worth of stats per player
            $numGames = rand(3, 8);
            $selectedGames = array_rand(array_flip($gameIds), $numGames);
            
            if (!is_array($selectedGames)) {
                $selectedGames = [$selectedGames];
            }

            foreach ($selectedGames as $gameId) {
                DB::table('player_stats')->insert([
                    'game_id' => $gameId,
                    'user_id' => $player->id,
                    'points' => rand(15, 35),
                    'rebounds' => rand(5, 15),
                    'assists' => rand(3, 12),
                    'steals' => rand(0, 4),
                    'blocks' => rand(0, 3),
                    'fg_made' => rand(6, 15),
                    'fg_attempts' => rand(12, 25),
                    'three_made' => rand(2, 8),
                    'three_attempts' => rand(5, 12),
                    'minutes_played' => rand(25, 40),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Create some clips for each player
            $numClips = rand(5, 15);
            for ($i = 0; $i < $numClips; $i++) {
                DB::table('clips')->insert([
                    'user_id' => $player->id,
                    'game_id' => $selectedGames[array_rand($selectedGames)],
                    'video_url' => 'https://example.com/clip_' . $player->id . '_' . $i . '.mp4',
                    'description' => 'Amazing play by ' . $player->name,
                    'status' => 'approved',
                    'duration' => rand(10, 60),
                    'is_highlight' => rand(0, 1),
                    'player_id' => $player->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Player stats and clips seeded successfully!');
    }
}
