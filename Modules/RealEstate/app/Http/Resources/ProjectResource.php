<?php
namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', $locale),
            'slug' => $this->slug,
            'description' => $this->getTranslation('description', $locale),
            'developer' => $this->developer,
            'agency' => $this->whenLoaded('agency', function() {
                return [
                    'id' => $this->agency->id,
                    'name' => $this->agency->name,
                    'logo' => $this->agency->avatar ? asset('storage/' . $this->agency->avatar) : null
                ];
            }),
            'location' => [
                'address' => $this->address,
                'city' => $this->city,
                'district' => $this->district,
                'ward' => $this->ward,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'full_address' => $this->full_address ?? null
            ],
            'details' => [
                'total_area' => $this->total_area,
                'total_area_formatted' => $this->total_area ? number_format($this->total_area, 2) . ' m²' : null,
                'total_units' => $this->total_units,
                'start_date' => $this->start_date?->format('Y-m-d'),
                'completion_date' => $this->completion_date?->format('Y-m-d'),
                'status' => $this->status,
                'status_label' => __("project::project.status.{$this->status}"),
                'progress' => $this->progress,
                'is_completed' => $this->is_completed
            ],
            'media' => [
                'images' => $this->getAllImagesAttribute(),
                'primary_image' => $this->primary_image,
                'virtual_tour' => $this->virtual_tour,
                'brochure_url' => $this->brochure_url,
                'video_url' => $this->video_url
            ],
            'amenities' => $this->whenLoaded('amenities', function() use ($locale) {
                return $this->amenities->map(function($amenity) use ($locale) {
                    return [
                        'id' => $amenity->id,
                        'name' => $amenity->name,
                        'icon' => $amenity->pivot->icon ?? $amenity->icon,
                        'category' => $amenity->category,
                        'description' => $amenity->pivot->description,
                        'value' => $amenity->pivot->value,
                        'is_highlight' => $amenity->pivot->is_highlight
                    ];
                });
            }),
            'highlighted_amenities' => $this->whenLoaded('highlightedAmenities', function() {
                return $this->highlightedAmenities->map(function($amenity) {
                    return [
                        'id' => $amenity->id,
                        'name' => $amenity->name,
                        'icon' => $amenity->pivot->icon ?? $amenity->icon,
                        'description' => $amenity->pivot->description
                    ];
                });
            }),
            'amenities_grouped' => $this->amenities_grouped,
            'statistics' => [
                'properties_count' => $this->property_count,
                'available_properties' => $this->available_property_count,
                'price_range' => $this->price_range,
                'area_range' => $this->area_range,
                'bedroom_types' => $this->bedroom_types
            ],
            'flags' => [
                'is_featured' => $this->is_featured,
                'is_active' => $this->is_active
            ],
            'seo' => [
                'meta_title' => $this->getTranslation('meta_title', $locale),
                'meta_description' => $this->getTranslation('meta_description', $locale),
                'meta_keywords' => $this->getTranslation('meta_keywords', $locale)
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}