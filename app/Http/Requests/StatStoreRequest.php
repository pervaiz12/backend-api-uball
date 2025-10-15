<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Policy on controller ensures only admin/staff
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'points' => ['required', 'integer', 'min:0'],
            'rebounds' => ['required', 'integer', 'min:0'],
            'assists' => ['required', 'integer', 'min:0'],
            'steals' => ['required', 'integer', 'min:0'],
            'blocks' => ['required', 'integer', 'min:0'],
            'fg_made' => ['required', 'integer', 'min:0'],
            'fg_attempts' => ['required', 'integer', 'min:0'],
            'three_made' => ['required', 'integer', 'min:0'],
            'three_attempts' => ['required', 'integer', 'min:0'],
            'minutes_played' => ['required', 'integer', 'min:0'],
        ];
    }
}
