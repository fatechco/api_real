<?php
namespace Modules\Package\Models;
use Illuminate\Database\Eloquent\Model;
use Modules\Package\Models\UserPackage;
use Modules\User\Models\User;
class CreditTransaction extends Model
{
    protected $table = 'credit_transactions';

    protected $fillable = [
        'user_id',
        'user_package_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array'
    ];

    protected $attributes = [
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

    public function isPurchase(): bool
    {
        return $this->type === 'purchase';
    }

    public function isUsage(): bool
    {
        return $this->type === 'usage';
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    public function isBonus(): bool
    {
        return $this->type === 'bonus';
    }

    public function getAmountFormattedAttribute(): string
    {
        return ($this->amount > 0 ? '+' : '') . number_format($this->amount);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}