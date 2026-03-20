<?php
namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AmenityResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'category' => $this->category,
            'category_label' => __("amenity::amenity.categories.{$this->category}"),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            
            'properties_count' => $this->when($this->properties_count !== null, $this->properties_count),
            'projects_count' => $this->when($this->projects_count !== null, $this->projects_count),
            
            'pivot' => $this->when($this->pivot, function() {
                return [
                    'value' => $this->pivot->value,
                    'description' => $this->pivot->description,
                    'icon' => $this->pivot->icon,
                    'is_highlight' => $this->pivot->is_highlight,
                    'order' => $this->pivot->order
                ];
            })
        ];
    }
}