<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Strict notification projection.
 *
 * Exposes ONLY the scalar fields the admin UI consumes. The `data` payload is
 * a JSON string on the database_notifications table; we decode it once here so
 * the frontend never has to re-parse it. No lazy relationships, no appended
 * model accessors, no N+1 — every field below is already loaded by the
 * select() projection in NotificationController.
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // `data` is stored as a JSON-encoded text column. Decode defensively:
        // it may already be an array when the model casts it.
        $data = $this->data;
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [];
        } elseif (! is_array($data)) {
            $data = [];
        }

        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'data'       => $data,
            'read_at'    => optional($this->read_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
