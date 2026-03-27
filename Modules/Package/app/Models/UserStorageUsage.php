<?php

namespace Modules\Package\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class UserStorageUsage extends Model
{
    protected $table = 'user_storage_usage';

    protected $fillable = [
        'user_id',
        'user_package_id',
        'total_used_bytes',
        'images_bytes',
        'videos_bytes',
        'documents_bytes',
        'other_bytes',
        'total_files_count',
        'images_count',
        'videos_count',
        'documents_count',
        'other_count',
        'listing_storage_usage',
        'last_reset_at',
        'last_calculated_at',
    ];

    protected $casts = [
        'total_used_bytes' => 'integer',
        'images_bytes' => 'integer',
        'videos_bytes' => 'integer',
        'documents_bytes' => 'integer',
        'other_bytes' => 'integer',
        'total_files_count' => 'integer',
        'images_count' => 'integer',
        'videos_count' => 'integer',
        'documents_count' => 'integer',
        'other_count' => 'integer',
        'listing_storage_usage' => 'array',
        'last_reset_at' => 'datetime',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the storage usage
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user package associated with this storage usage
     */
    public function userPackage(): BelongsTo
    {
        return $this->belongsTo(UserPackage::class, 'user_package_id');
    }

    /**
     * Get total used bytes formatted
     */
    public function getTotalUsedFormattedAttribute(): string
    {
        return $this->formatBytes($this->total_used_bytes);
    }

    /**
     * Get storage limit from package
     */
    public function getStorageLimitBytesAttribute(): int
    {
        if (!$this->userPackage || !$this->userPackage->package) {
            return 0;
        }
        
        $limits = $this->userPackage->package->limits;
        return ($limits['storage'] ?? 0) * 1024 * 1024;
    }

    /**
     * Get remaining storage bytes
     */
    public function getRemainingBytesAttribute(): int
    {
        $limit = $this->storage_limit_bytes;
        return max(0, $limit - $this->total_used_bytes);
    }

    /**
     * Get storage usage percentage
     */
    public function getUsagePercentageAttribute(): float
    {
        $limit = $this->storage_limit_bytes;
        if ($limit <= 0) {
            return 0;
        }
        return round(($this->total_used_bytes / $limit) * 100, 2);
    }

    /**
     * Get total files count formatted
     */
    public function getTotalFilesFormattedAttribute(): string
    {
        return number_format($this->total_files_count);
    }

    /**
     * Check if user is near storage limit (>= 90%)
     */
    public function isNearLimit(): bool
    {
        return $this->usage_percentage >= 90;
    }

    /**
     * Check if user has exceeded storage limit
     */
    public function isOverLimit(): bool
    {
        return $this->total_used_bytes > $this->storage_limit_bytes;
    }

    /**
     * Increment usage for a specific file type
     */
    public function incrementUsage(string $type, int $bytes, int $count = 1): void
    {
        $this->increment('total_used_bytes', $bytes);
        $this->increment('total_files_count', $count);
        
        $bytesField = "{$type}_bytes";
        $countField = "{$type}_count";
        
        if (property_exists($this, $bytesField)) {
            $this->increment($bytesField, $bytes);
            $this->increment($countField, $count);
        } else {
            $this->increment('other_bytes', $bytes);
            $this->increment('other_count', $count);
        }
        
        $this->update(['last_calculated_at' => now()]);
    }

    /**
     * Decrement usage for a specific file type
     */
    public function decrementUsage(string $type, int $bytes, int $count = 1): void
    {
        $this->decrement('total_used_bytes', $bytes);
        $this->decrement('total_files_count', $count);
        
        $bytesField = "{$type}_bytes";
        $countField = "{$type}_count";
        
        if (property_exists($this, $bytesField)) {
            $this->decrement($bytesField, $bytes);
            $this->decrement($countField, $count);
        } else {
            $this->decrement('other_bytes', $bytes);
            $this->decrement('other_count', $count);
        }
        
        $this->update(['last_calculated_at' => now()]);
    }

    /**
     * Update storage usage for a specific listing
     */
    public function updateListingUsage(int $propertyId, int $bytes, bool $increment = true): void
    {
        $listingUsage = $this->listing_storage_usage ?? [];
        $current = $listingUsage[$propertyId] ?? 0;
        
        if ($increment) {
            $listingUsage[$propertyId] = $current + $bytes;
        } else {
            $newValue = $current - $bytes;
            if ($newValue <= 0) {
                unset($listingUsage[$propertyId]);
            } else {
                $listingUsage[$propertyId] = $newValue;
            }
        }
        
        $this->listing_storage_usage = $listingUsage;
        $this->save();
    }

    /**
     * Reset monthly storage usage
     */
    public function resetMonthly(): void
    {
        $this->update([
            'listing_storage_usage' => [],
            'last_reset_at' => now(),
        ]);
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Scope for users with high storage usage
     */
    public function scopeHighUsage($query, float $percentage = 80)
    {
        return $query->whereRaw('(total_used_bytes / (SELECT limits->>"$.storage" * 1024 * 1024 FROM packages WHERE id = user_package_id)) * 100 >= ?', [$percentage]);
    }

    /**
     * Scope for users who are over limit
     */
    public function scopeOverLimit($query)
    {
        return $query->whereRaw('total_used_bytes > (SELECT limits->>"$.storage" * 1024 * 1024 FROM packages WHERE id = user_package_id)');
    }

    /**
     * Get storage breakdown for dashboard
     */
    public function getBreakdownAttribute(): array
    {
        return [
            'images' => [
                'size' => $this->formatBytes($this->images_bytes),
                'size_bytes' => $this->images_bytes,
                'count' => $this->images_count,
                'percentage' => $this->total_used_bytes > 0 
                    ? round(($this->images_bytes / $this->total_used_bytes) * 100, 2) 
                    : 0,
            ],
            'videos' => [
                'size' => $this->formatBytes($this->videos_bytes),
                'size_bytes' => $this->videos_bytes,
                'count' => $this->videos_count,
                'percentage' => $this->total_used_bytes > 0 
                    ? round(($this->videos_bytes / $this->total_used_bytes) * 100, 2) 
                    : 0,
            ],
            'documents' => [
                'size' => $this->formatBytes($this->documents_bytes),
                'size_bytes' => $this->documents_bytes,
                'count' => $this->documents_count,
                'percentage' => $this->total_used_bytes > 0 
                    ? round(($this->documents_bytes / $this->total_used_bytes) * 100, 2) 
                    : 0,
            ],
            'other' => [
                'size' => $this->formatBytes($this->other_bytes),
                'size_bytes' => $this->other_bytes,
                'count' => $this->other_count,
                'percentage' => $this->total_used_bytes > 0 
                    ? round(($this->other_bytes / $this->total_used_bytes) * 100, 2) 
                    : 0,
            ],
        ];
    }
}