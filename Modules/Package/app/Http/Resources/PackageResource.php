<?php
namespace Modules\Package\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'role_name' => $this->role_name,
            'price' => $this->price,
            'credits_per_month' => $this->credits_per_month,
            'max_agents' => $this->max_agents,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'limits' => $this->limits,
            'features' => $this->features,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}