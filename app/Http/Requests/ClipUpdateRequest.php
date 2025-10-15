<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClipUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Policy enforces update permissions
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:pending,approved,rejected'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'player_id' => ['sometimes', 'nullable', 'exists:users,id'],
        ];
    }
}
