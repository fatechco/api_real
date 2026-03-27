<?php
namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyListResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();
        
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->translate($locale)->title,
            'slug' => $this->slug,
            'description' => $this->translate($locale)->description,
            'price' => $this->price,
            'price_formatted' => $this->price_formatted,
            'area' => $this->area,
            'area_formatted' => $this->area_formatted,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'address' => $this->full_address,
            'city' => $this->city,
            'district' => $this->district,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'is_featured' => (bool) $this->is_featured,
            'is_vip' => (bool) $this->is_vip,
            'views' => $this->views,
            'primary_image' => $this->primary_image_url,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar_url,
                ];
            }),
        ];
    }
}