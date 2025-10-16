<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClipUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Policy will enforce create permissions
    }

    public function rules(): array
    {
        return [
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/webm', 'max:512000'], // 500MB
            
            // Basic fields (accept both camelCase and snake_case)
            'gameId' => ['required', 'exists:games,id'],
            'game_id' => ['sometimes', 'exists:games,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'playerId' => ['nullable', 'exists:users,id'],
            'player_id' => ['sometimes', 'exists:users,id'],
            'duration' => ['nullable', 'integer', 'min:0'],
            
            // New enhanced fields
            'tags' => ['nullable', 'string'], // JSON string
            'videoUrl' => ['nullable', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png', 'max:5120'], // 5MB
            
            // Game result fields
            'teamName' => ['nullable', 'string', 'max:100'],
            'opponentTeam' => ['nullable', 'string', 'max:100'],
            'gameResult' => ['nullable', 'in:win,loss'],
            'teamScore' => ['nullable', 'integer', 'min:0'],
            'opponentScore' => ['nullable', 'integer', 'min:0'],
            
            // Visibility and display options
            'visibility' => ['nullable', 'in:public,private,pending'],
            'showInTrending' => ['nullable', 'boolean'],
            'showInProfile' => ['nullable', 'boolean'],
            'featureOnDashboard' => ['nullable', 'boolean'],
            'season' => ['nullable', 'string', 'max:4'],
            
            // Court and date
            'court' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            
            // Optional stat fields
            'points' => ['nullable', 'integer', 'min:0'],
            'rebounds' => ['nullable', 'integer', 'min:0'],
            'assists' => ['nullable', 'integer', 'min:0'],
            'steals' => ['nullable', 'integer', 'min:0'],
            'blocks' => ['nullable', 'integer', 'min:0'],
            'fg_made' => ['nullable', 'integer', 'min:0'],
            'fg_attempts' => ['nullable', 'integer', 'min:0'],
            'three_made' => ['nullable', 'integer', 'min:0'],
            'three_attempts' => ['nullable', 'integer', 'min:0'],
            'minutes_played' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
