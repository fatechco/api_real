<?php

namespace Modules\Location\Http\Resources;

use App\Http\Resources\TranslationResource;
use Modules\Location\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
      
        $locale = app()->getLocale();

        return [
            'id'            => $this->id,
            'active'        => (bool)$this->active,
            'name' => $this->translate($locale)->name,
           // 'country'       => CountryResource::make($this->whenLoaded('country')),
           // 'province'          => ProvinceResource::make($this->whenLoaded('province')),
           // 'district'          => DistrictResource::make($this->whenLoaded('district')),
        ];
    }
}
