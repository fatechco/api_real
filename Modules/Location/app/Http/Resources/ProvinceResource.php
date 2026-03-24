<?php

namespace Modules\Location\Http\Resources;

use App\Http\Resources\TranslationResource;
use Modules\Location\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Province|JsonResource $this */
        $locale = app()->getLocale();

         
        return [
            'id'            => $this->id,
            'active'        => (bool)$this->active,
            'country_id'    => $this->when($this->country_id, $this->country_id),
            'name'=> $this->translate($locale)->name,
        ];
    }
}
