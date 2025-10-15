<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Build a RELATIVE video path so frontend can prepend host/port from its env
        $rawUrl = (string) $this->video_url;
        if (str_starts_with($rawUrl, 'http')) {
            $path = parse_url($rawUrl, PHP_URL_PATH) ?: $rawUrl;
        } else {
            $path = $rawUrl;
        }
        $relativeVideoPath = ltrim($path, '/'); // e.g., 'storage/clips/abc.mp4'

        return [
            'id' => $this->id,
            'video_url' => $relativeVideoPath,
            'duration' => $this->duration,
            'description' => $this->description,
            'status' => $this->status,
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'liked_by_me' => (bool) ($this->liked_by_me ?? false),
            'user' => new UserSmallResource($this->whenLoaded('user') ?? $this->user),
            'game' => new GameResource($this->whenLoaded('game') ?? $this->game),
            'player' => $this->when($this->relationLoaded('player') || $this->player, function () {
                return new UserSmallResource($this->whenLoaded('player') ?? $this->player);
            }),
            'created_at' => $this->created_at,
        ];
    }
}
