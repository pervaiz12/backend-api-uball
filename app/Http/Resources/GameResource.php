<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location,
            'game_date' => $this->game_date,
            'created_by' => $this->created_by,
            'result' => $this->result ?? null,
            'team_score' => $this->team_score ?? null,
            'opponent_score' => $this->opponent_score ?? null,
            'player_stats' => $this->player_stats ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
