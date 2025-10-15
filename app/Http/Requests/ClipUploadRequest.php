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
            'game_id' => ['required', 'exists:games,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'player_id' => ['nullable', 'exists:users,id'],
            'duration' => ['nullable', 'integer', 'min:0'],
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
