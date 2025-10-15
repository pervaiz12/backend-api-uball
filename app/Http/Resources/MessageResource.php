<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $iso = function ($value) {
            if (!$value) {
                return null;
            }
            // If it's already a Carbon instance
            if ($value instanceof \Illuminate\Support\Carbon) {
                return $value->toISOString();
            }
            // Try to parse strings or other date-like inputs
            try {
                return \Illuminate\Support\Carbon::parse($value)->toISOString();
            } catch (\Throwable $e) {
                // Fallback to raw string if parsing fails
                return (string) $value;
            }
        };

        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'sender' => new UserSmallResource($this->whenLoaded('sender') ?? $this->sender),
            'receiver' => new UserSmallResource($this->whenLoaded('receiver') ?? $this->receiver),
            'body' => $this->body,
            'attachment_path' => $this->attachment_path,
            'attachment_type' => $this->attachment_type,
            'attachment_name' => $this->attachment_name,
            'attachment_size' => $this->attachment_size,
            'attachment_url' => (function () {
                $path = $this->attachment_path;
                if (!$path) {
                    return null;
                }
                // Absolute URL stored
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                    return $path;
                }
                // Legacy path already prefixed with 'storage/'
                if (str_starts_with($path, 'storage/')) {
                    return url($path);
                }
                // Default: public disk relative path like 'messages/filename.ext'
                return url(\Illuminate\Support\Facades\Storage::url($path));
            })(),
            'read_at' => $iso($this->read_at),
            'created_at' => $iso($this->created_at),
            'updated_at' => $iso($this->updated_at),
        ];
    }
}
