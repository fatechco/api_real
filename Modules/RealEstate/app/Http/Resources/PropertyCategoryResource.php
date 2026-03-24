<?php
namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->translate($locale)->name,
            'slug' => $this->slug,
            'description' => $this->translate($locale)->description,
            'icon' => $this->icon,
            //'image' => $this->image ? asset('storage/' . $this->image) : null,
            //'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function() use ($locale) {
                return [
                    'id' => $this->parent->id,
                    'name' => $this->parent->translate($locale)->name,
                    'slug' => $this->parent->slug
                ];
            }),
           // 'children' => PropertyCategoryResource::collection($this->whenLoaded('children')),
            'order' => $this->order,
            'is_active' => $this->is_active,
           // 'properties_count' => $this->when($this->properties_count !== null, $this->properties_count),
        
        ];
    }
}