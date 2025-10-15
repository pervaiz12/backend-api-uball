<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_and_show_games(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $games = Game::factory()->count(2)->create([
            'created_by' => $staff->id,
            'location' => 'Test Court',
            'game_date' => now()->addDay(),
        ]);

        $res = $this->getJson('/api/games');
        $res->assertOk()->assertJsonStructure([
            'data' => [
                ['id','location','game_date','created_by']
            ]
        ]);

        $one = $this->getJson('/api/games/'.$games->first()->id);
        $one->assertOk()->assertJsonStructure(['data' => ['id','location','game_date','created_by']]);
    }

    public function test_staff_can_create_update_delete_game(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($staff);

        // create
        $create = $this->postJson('/api/games', [
            'location' => 'Main Arena',
            'game_date' => now()->addDays(2)->toISOString(),
        ]);
        $create->assertCreated()->assertJsonStructure(['data' => ['id','location','game_date','created_by']]);
        $id = $create->json('data.id');

        // update
        $update = $this->putJson('/api/games/'.$id, [
            'location' => 'Updated Arena',
        ]);
        $update->assertOk()->assertJsonPath('data.location', 'Updated Arena');

        // delete
        $delete = $this->deleteJson('/api/games/'.$id);
        $delete->assertNoContent();

        $this->assertDatabaseMissing('games', ['id' => $id]);
    }

    public function test_player_cannot_create_game(): void
    {
        $player = User::factory()->create(['role' => 'player']);
        Sanctum::actingAs($player);

        $resp = $this->postJson('/api/games', [
            'location' => 'Court Z',
            'game_date' => now()->addDays(1)->toISOString(),
        ]);
        $resp->assertForbidden();
    }
}
