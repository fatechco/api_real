<?php
namespace Modules\Package\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    protected $table = 'packages';

    protected $fillable = [
        'name',
        'type',
        'role_name',
        'price',
        'credits_per_month',
        'max_agents',
        'is_active',
        'sort_order',
        'limits',
        'features'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'credits_per_month' => 'integer',
        'max_agents' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'limits' => 'array',
        'features' => 'array'
    ];

    protected $attributes = [
        'limits' => '{"listingsPerMonth":0,"vipListings":0,"teamMembers":0,"apiCalls":0,"storage":0}',
        'features' => '[]'
    ];

    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }

    public function isFree(): bool
    {
        return $this->price == 0;
    }

    public function getListingLimit(): int
    {
        return data_get($this->limits, 'listingsPerMonth', 0);
    }

    public function getVipLimit(): int
    {
        return data_get($this->limits, 'vipListings', 0);
    }

    public function getTeamMemberLimit(): int
    {
        return data_get($this->limits, 'teamMembers', 0);
    }

    public function getApiCallLimit(): int
    {
        return data_get($this->limits, 'apiCalls', 0);
    }

    public function getStorageLimit(): int
    {
        return data_get($this->limits, 'storage', 0);
    }

    public function hasFeature(string $code): bool
    {
        return collect($this->features)
            ->where('code', $code)
            ->where('enabled', true)
            ->isNotEmpty();
    }

    public function getEnabledFeatures(): array
    {
        return collect($this->features)
            ->where('enabled', true)
            ->pluck('code')
            ->toArray();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}