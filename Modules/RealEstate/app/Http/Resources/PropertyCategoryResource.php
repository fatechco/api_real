<?php
namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'parent_id' => $this->parent_id,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,
            'translations' => $this->whenLoaded('translations', function() {
                return $this->translations->map(function($translation) {
                    return [
                        'locale' => $translation->locale,
                        'name' => $translation->name,
                        'description' => $translation->description,
                    ];
                });
            }),
            'parent' => $this->whenLoaded('parent', function() {
                return [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                    'slug' => $this->parent->slug,
                ];
            }),
            'children' => $this->whenLoaded('children', function() {
                return PropertyCategoryResource::collection($this->children);
            }),
            'property_count' => $this->when(isset($this->properties_count), $this->properties_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}