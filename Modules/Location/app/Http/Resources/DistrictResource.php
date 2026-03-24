<?php
namespace Modules\Location\Http\Resources;

use App\Http\Resources\TranslationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Location\Models\District;

class DistrictResource extends JsonResource
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
            'country_id'    => $this->when($this->country_id, $this->country_id),
            'province_id'   => $this->when($this->province_id, $this->province_id),
            'name'   => $this->translate($locale)->name,

           // 'country'       => CountryResource::make($this->whenLoaded('country')),
            //'province'          => ProvinceResource::make($this->whenLoaded('province')),
            //'ward'        => WardResource::make($this->whenLoaded('ward')),
        ];
    }
}
