<?php
namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
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
            'content' => $this->translate($locale)->content,
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'avatar' => $this->user->avatar ? asset('storage/' . $this->user->avatar) : null,
                    'is_agent' => $this->user->hasRole('agent'),
                    'is_agency' => $this->user->hasRole('agency')
                ];
            }),
            
            'project' => $this->whenLoaded('project', function() use ($locale) {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->getTranslation('name', $locale),
                    'slug' => $this->project->slug,
                    'developer' => $this->project->developer
                ];
            }),
            
            'category' => $this->whenLoaded('category', function() use ($locale) {
                return [
                    'name' => $this->category->translate($locale)->name,
                    'slug' => $this->category->slug
                ];
            }),
            'type' => $this->whenLoaded('type', function() {
                return [
                    'name' => $this->type->name,
                    'slug' => $this->type->slug
                ];
            }),
            
            'price' => $this->price,
            'price_formatted' => $this->price_formatted,
            'price_per_m2' => $this->price_per_m2,
            'price_per_m2_formatted' => $this->price_per_m2 ? number_format($this->price_per_m2) . ' ₫/m²' : null,
            'is_negotiable' => $this->is_negotiable,
            
            'area' => $this->area,
            'area_formatted' => $this->area_formatted,
            'land_area' => $this->land_area,
            'land_area_formatted' => $this->land_area ? number_format($this->land_area, 2) . ' m²' : null,
            'built_area' => $this->built_area,
            'built_area_formatted' => $this->built_area ? number_format($this->built_area, 2) . ' m²' : null,
            
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'floors' => $this->floors,
            'garages' => $this->garages,
            'year_built' => $this->year_built,
            'furnishing' => $this->furnishing,
            'furnishing_label' => $this->furnishing ? __("property::property.furnishing.{$this->furnishing}") : null,
            'legal_status' => $this->legal_status,
            'ownership_type' => $this->ownership_type,
            
            'address' => $this->address,
            'city' => $this->city,
            'district' => $this->district,
            'ward' => $this->ward,
            'street' => $this->street,
            'project_name' => $this->project_name,
            'full_address' => $this->full_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'map_url' => $this->map_url,
            
            'status' => $this->status,
            'status_label' => __("property::property.status.{$this->status}"),
            'transaction_type' => $this->transaction_type,
            'transaction_type_label' => __("property::property.transaction_type.{$this->transaction_type}"),
            
            'is_featured' => $this->is_featured,
            'is_vip' => $this->is_vip,
            'is_vip_active' => $this->is_vip_active,
            'vip_expires_at' => $this->vip_expires_at?->format('Y-m-d H:i:s'),
           // 'vip_remaining_days' => $this->getRemainingVipDays(),
            'is_urgent' => $this->is_urgent,
            'is_top' => $this->is_top,
            'is_top_active' => $this->is_top_active,
            'top_expires_at' => $this->top_expires_at?->format('Y-m-d H:i:s'),
            
            'images' => PropertyImageResource::collection($this->whenLoaded('images')),
            'primary_image' => $this->primary_image_url,
            'all_images' => $this->all_images,
            
            'amenities' => $this->whenLoaded('amenities', function() use ($locale) {
                return $this->amenities->map(function($amenity) use ($locale) {
                    return [
                        'id' => $amenity->id,
                        'name' => $amenity->name,
                        'icon' => $amenity->icon,
                        'category' => $amenity->category,
                        'category_label' => __("amenity::amenity.categories.{$amenity->category}"),
                        'value' => $amenity->pivot->value ?? null
                    ];
                });
            }),
            
            'views' => $this->views,
            'unique_views' => $this->unique_views,
            'contact_views' => $this->contact_views,
            'favorites_count' => $this->favorites_count,
            
            'meta_title' => $this->getTranslation('meta_title', $locale),
            'meta_description' => $this->getTranslation('meta_description', $locale),
            'meta_keywords' => $this->getTranslation('meta_keywords', $locale),
            
            'assigned_agents' => $this->whenLoaded('assignedAgents', function() {
                return $this->assignedAgents->map(function($agent) {
                    return [
                        'id' => $agent->id,
                        'name' => $agent->name,
                        'avatar' => $agent->avatar ? asset('storage/' . $agent->avatar) : null,
                        'type' => $agent->pivot->type,
                        'commission_rate' => $agent->pivot->commission_rate
                    ];
                });
            }),
            'primary_agent' => $this->whenLoaded('primaryAgent', function() {
                $agent = $this->primaryAgent->first();
                if ($agent) {
                    return [
                        'id' => $agent->id,
                        'name' => $agent->name,
                        'avatar' => $agent->avatar ? asset('storage/' . $agent->avatar) : null,
                        'commission_rate' => $agent->pivot->commission_rate
                    ];
                }
                return null;
            }),
            
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'expired_at' => $this->expired_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            'can_edit' => $this->when(auth()->check(), function() {
                return $this->canEdit(auth()->id());
            }),
            'can_delete' => $this->when(auth()->check(), function() {
                return $this->canEdit(auth()->id());
            }),
           /* 'is_favorited' => $this->when(auth()->check(), function() {
                return $this->favoritedBy()
                    ->where('user_id', auth()->id())
                    ->exists();
            })*/
        ];
    }
}