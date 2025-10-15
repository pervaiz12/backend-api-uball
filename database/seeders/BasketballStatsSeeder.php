<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class BasketballStatsSeeder extends Seeder
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

        foreach ($users as $user) {
            // Generate realistic basketball stats
            $gamesPlayed = rand(15, 82); // NBA season range
            $avgPoints = rand(8, 35) + (rand(0, 9) / 10); // 8.0 to 35.9 points
            $avgRebounds = rand(2, 15) + (rand(0, 9) / 10); // 2.0 to 15.9 rebounds
            $avgAssists = rand(1, 12) + (rand(0, 9) / 10); // 1.0 to 12.9 assists
            $fieldGoalPct = rand(35, 65) + (rand(0, 99) / 100); // 35% to 65% FG%
            
            $user->update([
                'avg_points' => $avgPoints,
                'avg_rebounds' => $avgRebounds,
                'avg_assists' => $avgAssists,
                'field_goal_percentage' => $fieldGoalPct,
                'total_games_played' => $gamesPlayed,
            ]);
        }

        $this->command->info('Basketball stats seeded successfully for all players!');
    }
}
