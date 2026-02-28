<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReportResource extends JsonResource
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
            'client_uuid' => $this->client_uuid,
            'report_type' => new ReportTypeResource($this->whenLoaded('reportType')),
            'description' => $this->getTranslations('description'),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'inspector_feedback' => $this->getTranslations('inspector_feedback'),
            'is_synchronized' => $this->is_synchronized,
            'media_attachments' => collect($this->media_attachments ?? [])->map(fn ($path) => Storage::disk('public')->url($path)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
