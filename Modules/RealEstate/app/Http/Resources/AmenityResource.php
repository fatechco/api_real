<?php
// Modules/RealEstate/Http/Resources/AmenityResource.php

namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmenityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translate($locale)->name,
            'icon' => $this->icon,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,
        ];
    }
}