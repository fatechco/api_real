<?php
namespace Modules\Package\Models;
use Illuminate\Database\Eloquent\Model;
use Modules\Package\Models\UserPackage;
use Modules\User\Models\User;

class UsageLog extends Model
{
    protected $table = 'usage_logs';

    protected $fillable = [
        'user_id',
        'user_package_id',
        'resource_type',
        'quantity',
        'deducted_credits',
        'credits_used',
        'reference_type',
        'reference_id',
        'metadata'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'deducted_credits' => 'boolean',
        'credits_used' => 'integer',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'quantity' => 1,
        'deducted_credits' => false,
        'credits_used' => 0,
        'metadata' => '{}'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userPackage()
    {
        return $this->belongsTo(UserPackage::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function getResourceTypeLabelAttribute(): string
    {
        return match($this->resource_type) {
            'listing' => 'list Normal',
            'vip_listing' => 'list VIP',
            'api_call' => 'API Call',
            'storage' => 'Storage',
            default => $this->resource_type
        };
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByResourceType($query, string $type)
    {
        return $query->where('resource_type', $type);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}