<?php
namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyImageResource extends JsonResource
{
    /**
     * Simple version for listing pages
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'thumbnail' => $this->thumbnail_url,
            'caption' => $this->caption,
            'is_primary' => (bool) $this->is_primary,
        ];
    }
}