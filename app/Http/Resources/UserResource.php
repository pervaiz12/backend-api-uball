<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_photo' => $this->profile_photo,
            'games_count' => (int) ($this->games_count ?? 0),
            'clips_count' => (int) ($this->clips_count ?? 0),
            'last_login' => $this->last_login ? $this->last_login->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'home_court' => $this->home_court,
            'city' => $this->city,
            'role' => $this->role,
            'is_official' => (bool) $this->is_official,
            'can_upload_clips' => (bool) ($this->can_upload_clips ?? false),
        ];
    }
}
