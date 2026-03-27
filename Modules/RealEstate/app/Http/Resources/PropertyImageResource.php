<?php
// Modules/RealEstate/Http/Resources/PropertyImageResource.php

namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyImageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'url' => $this->url,
            'thumbnail' => $this->thumbnail_url,
            'caption' => $this->caption,
            'is_primary' => (bool) ($this->pivot->is_primary ?? $this->is_primary ?? false),
            'order' => $this->pivot->order ?? $this->order ?? 0,
            'file_category' => $this->file_category,
            'mime_type' => $this->mime_type,
            'size' => $this->size_formatted,
        ];
    }
}