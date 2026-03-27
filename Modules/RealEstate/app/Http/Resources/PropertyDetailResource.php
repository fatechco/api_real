<?php
namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyDetailResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();
        
        return [
            // Basic info
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->translate($locale)->title,
            'slug' => $this->slug,
            'description' => $this->translate($locale)->description,
            'content' => $this->translate($locale)->content,
            
            // User & Agent
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'avatar' => $this->user->avatar_url,
                    'is_agent' => $this->user->hasRole('agent'),
                    'is_agency' => $this->user->hasRole('agency'),
                ];
            }),
            
            // Category & Project
            'category' => $this->whenLoaded('category', function() use ($locale) {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->translate($locale)->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'project' => $this->whenLoaded('project', function() use ($locale) {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->getTranslation('name', $locale),
                    'slug' => $this->project->slug,
                ];
            }),
            
            // Pricing
            'price' => $this->price,
            'price_formatted' => $this->price_formatted,
            'price_per_m2' => $this->price_per_m2,
            'price_per_m2_formatted' => $this->price_per_m2 ? number_format($this->price_per_m2) . ' ₫/m²' : null,
            'is_negotiable' => (bool) $this->is_negotiable,
            
            // Area & Dimensions
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
            
            // Location
            'address' => $this->full_address,
            'city' => $this->city,
            'district' => $this->district,
            'ward' => $this->ward,
            'street' => $this->street,
            'project_name' => $this->project_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'map_url' => $this->map_url,
            
            // Status
            'status' => $this->status,
            'status_label' => __("property::property.status.{$this->status}"),
            'type' => $this->type,
            'type_label' => $this->type_label,
            
            // Premium features
            'is_featured' => (bool) $this->is_featured,
            'is_vip' => (bool) $this->is_vip,
            'is_vip_active' => $this->is_vip_active,
            'vip_expires_at' => $this->vip_expires_at?->format('Y-m-d H:i:s'),
            'is_urgent' => (bool) $this->is_urgent,
            'is_top' => (bool) $this->is_top,
            'is_top_active' => $this->is_top_active,
            'top_expires_at' => $this->top_expires_at?->format('Y-m-d H:i:s'),
            
            // Media
            'images' => PropertyImageResource::collection($this->whenLoaded('images')),
            'primary_image' => $this->primary_image_url,
            'all_images' => $this->all_images,
            'video_url' => $this->video_url,
            'virtual_tour_url' => $this->virtual_tour_url,
            
            // Amenities
            'amenities' => $this->whenLoaded('amenities', function() use ($locale) {
                return $this->amenities->map(function($amenity) use ($locale) {
                    return [
                        'id' => $amenity->id,
                        'name' => $amenity->name,
                        'icon' => $amenity->icon,
                        'value' => $amenity->pivot->value ?? null,
                    ];
                });
            }),
            
            // Statistics
            'views' => $this->views,
            'unique_views' => $this->unique_views,
            'contact_views' => $this->contact_views,
            'favorites_count' => $this->favorites_count,
            
            // SEO
            'meta_title' => $this->getTranslation('meta_title', $locale),
            'meta_description' => $this->getTranslation('meta_description', $locale),
            'meta_keywords' => $this->getTranslation('meta_keywords', $locale),
            
            // Assigned Agents
            'assigned_agents' => $this->whenLoaded('assignedAgents', function() {
                return $this->assignedAgents->map(function($agent) {
                    return [
                        'id' => $agent->id,
                        'name' => $agent->name,
                        'avatar' => $agent->avatar_url,
                        'type' => $agent->pivot->type,
                        'commission_rate' => $agent->pivot->commission_rate,
                    ];
                });
            }),
            'primary_agent' => $this->whenLoaded('primaryAgent', function() {
                $agent = $this->primaryAgent->first();
                if ($agent) {
                    return [
                        'id' => $agent->id,
                        'name' => $agent->name,
                        'avatar' => $agent->avatar_url,
                        'commission_rate' => $agent->pivot->commission_rate,
                    ];
                }
                return null;
            }),
            
            // Timestamps
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'expired_at' => $this->expired_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Permissions
            'can_edit' => $this->when(auth()->check(), function() {
                return $this->canEdit(auth()->id());
            }),
            'can_delete' => $this->when(auth()->check(), function() {
                return $this->canEdit(auth()->id());
            }),
        ];
    }
}