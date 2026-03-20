<?php
namespace Modules\Package\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserPackageResource extends JsonResource
{
    public function toArray()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'package_id' => $this->package_id,
            'status' => $this->status,
            'credits_remaining' => $this->credits_remaining,
            'listings_used_this_month' => $this->listings_used_this_month,
            'bonus_credits' => $this->bonus_credits,
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'package' => PackageResource::make($this->whenLoaded('package')),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}