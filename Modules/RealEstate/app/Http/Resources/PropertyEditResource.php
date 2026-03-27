<?php
namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyEditResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();
        
        return [
            // Basic info for form
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->translate($locale)->title,
            'description' => $this->translate($locale)->description,
            'category' => $this->whenLoaded('category', function() use ($locale) {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->translate($locale)->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'type' => $this->type,
            'status' => $this->status,
            'price' => $this->price,
            'is_negotiable' => (bool) $this->is_negotiable,

            
            // Area & Dimensions
            'area' => $this->area,
            'land_area' => $this->land_area,
            'built_area' => $this->built_area,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'floors' => $this->floors,
            'garages' => $this->garages,
            'year_built' => $this->year_built,
            'furnishing' => $this->furnishing,
            'legal_status' => $this->legal_status,
            'ownership_type' => $this->ownership_type,
            
            // Location
            'country_id' => $this->country_id,
            'province_id' => $this->province_id,
            'district_id' => $this->district_id,
            'ward_id' => $this->ward_id,
            'street' => $this->street,
            'street_number' => $this->street_number,
            'building_name' => $this->building_name,
            'full_address' => $this->full_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            
            // Media
            'existing_images' => $this->whenLoaded('images', function() {
                return $this->images->map(function($image) {
                    return [
                        'id' => $image->id,
                        'uuid' => $image->uuid,
                        'url' => $image->url,
                        'thumbnail' => $image->thumbnail_url,
                        'is_primary' => (bool) ($image->pivot->is_primary ?? false),
                        'order' => $image->pivot->order ?? 0,
                        'caption' => $image->caption,
                    ];
                });
            }),
            'video_url' => $this->video_url,
            'virtual_tour_url' => $this->virtual_tour_url,
            
            // Amenities (IDs for multi-select)
            'amenities' => $this->whenLoaded('amenities', function() {
                return $this->amenities->pluck('id');
            }),
            
            // Flags
            'is_featured' => (bool) $this->is_featured,
            'is_vip' => (bool) $this->is_vip,
            'is_urgent' => (bool) $this->is_urgent,
            'is_top' => (bool) $this->is_top,
            
            // Translations for multi-language
            'translations' => $this->whenLoaded('translations', function() {
                return $this->translations->mapWithKeys(function($translation) {
                    return [
                        $translation->locale => [
                            'title' => $translation->title,
                            'description' => $translation->description,
                            'content' => $translation->content,
                        ]
                    ];
                });
            }),
        ];
    }
}