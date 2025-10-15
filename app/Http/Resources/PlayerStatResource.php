<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerStatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game_id' => $this->game_id,
            'user_id' => $this->user_id,
            'points' => $this->points,
            'rebounds' => $this->rebounds,
            'assists' => $this->assists,
            'steals' => $this->steals,
            'blocks' => $this->blocks,
            'fg_made' => $this->fg_made,
            'fg_attempts' => $this->fg_attempts,
            'three_made' => $this->three_made,
            'three_attempts' => $this->three_attempts,
            'minutes_played' => $this->minutes_played,
            'user' => new UserSmallResource($this->whenLoaded('user')),
        ];
    }
}
