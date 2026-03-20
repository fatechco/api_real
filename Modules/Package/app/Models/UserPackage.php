<?php
namespace Modules\Package\Models;
use Illuminate\Database\Eloquent\Model;
use Modules\Package\Models\Package;
use Modules\User\Models\User;

class UserPackage extends Model
{
    protected $table = 'user_packages';

    protected $fillable = [
        'user_id',
        'package_id',
        'status',
        'credits_remaining',
        'listings_used_this_month',
        'bonus_credits',
        'last_reset_at',
        'started_at',
        'expires_at',
        'cancelled_at'
    ];

    protected $casts = [
        'credits_remaining' => 'integer',
        'listings_used_this_month' => 'integer',
        'bonus_credits' => 'integer',
        'last_reset_at' => 'datetime',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(UsageLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function canUseListing(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $limit = $this->package->getListingLimit();
        return $this->listings_used_this_month < $limit;
    }

    public function canUseVip(): bool
    {
        if (!$this->isActive() || !$this->package->hasFeature('listing.vip')) {
            return false;
        }

        $vipLimit = $this->package->getVipLimit();
        $vipUsed = $this->user->properties()
            ->where('is_vip', true)
            ->whereMonth('created_at', now()->month)
            ->count();

        return $vipUsed < $vipLimit;
    }

    public function getAvailableCredits(): int
    {
        return $this->credits_remaining + $this->bonus_credits;
    }

    public function getUsagePercentage(): int
    {
        $limit = $this->package->getListingLimit();
        if ($limit === 0) {
            return 0;
        }

        return round(($this->listings_used_this_month / $limit) * 100);
    }

    public function getRemainingDays(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    public function activate(): self
    {
        $this->update([
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
            'cancelled_at' => null
        ]);

        return $this;
    }

    public function deactivate(): self
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return $this;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '<', now());
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}