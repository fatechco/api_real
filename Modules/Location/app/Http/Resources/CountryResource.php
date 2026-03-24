<?php
namespace Modules\Location\Http\Resources;

use Modules\Location\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        /** @var Country|JsonResource $this */
        $locale = app()->getLocale();
        return [
            'id' => $this->id,
            'code' => $this->code,
            'phone_code' => $this->phone_code,
            'active' => (bool) $this->active,
            'is_default' => (bool) $this->is_default,
            'order' => $this->order,
            
            // Translated fields
            'name' => $this->translate($locale)->name,
            
            // Relations
            
           // 'provinces' => ProvinceResource::collection($this->whenLoaded('provinces')),
           // 'districts' => DistrictResource::collection($this->whenLoaded('districts')),
           // 'wards' => WardResource::collection($this->whenLoaded('wards')),
            
            // Counts
            //'provinces_count' => $this->when($this->provinces_count !== null, $this->provinces_count),
            //'districts_count' => $this->when($this->districts_count !== null, $this->districts_count),
            //'wards_count' => $this->when($this->wards_count !== null, $this->wards_count),
            
        ];
    }
}