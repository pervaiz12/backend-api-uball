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
            'external_video_url' => $this->external_video_url,
            'thumbnail_url' => $this->thumbnail_url,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags ?? [],
            'team_name' => $this->team_name,
            'opponent_team' => $this->opponent_team,
            'game_result' => $this->game_result,
            'team_score' => $this->team_score,
            'opponent_score' => $this->opponent_score,
            'fg_percentage' => $this->fg_percentage,
            'three_pt_percentage' => $this->three_pt_percentage,
            'four_pt_percentage' => $this->four_pt_percentage,
            'duration' => $this->duration,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'show_in_trending' => (bool) $this->show_in_trending,
            'show_in_profile' => (bool) $this->show_in_profile,
            'feature_on_dashboard' => (bool) $this->feature_on_dashboard,
            'season' => $this->season,
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'views_count' => (int) ($this->views_count ?? 0),
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
