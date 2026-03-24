<?php
namespace Modules\Location\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryTranslationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'locale' => $this->locale,
            'name' => $this->name,
            'native_name' => $this->native_name,
        ];
    }
}