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
            'name' => $this->getTranslation('name', $locale),
            'slug' => $this->slug,
            'description' => $this->getTranslation('description', $locale),
            'icon' => $this->icon,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function() use ($locale) {
                return [
                    'id' => $this->parent->id,
                    'name' => $this->parent->getTranslation('name', $locale),
                    'slug' => $this->parent->slug
                ];
            }),
            'children' => PropertyCategoryResource::collection($this->whenLoaded('children')),
            'full_name' => $this->full_name,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'properties_count' => $this->when($this->properties_count !== null, $this->properties_count),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}