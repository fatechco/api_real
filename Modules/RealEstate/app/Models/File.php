<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Package\Models\UserPackage;
use Modules\User\Models\User;

class File extends Model
{
    use SoftDeletes;
    
    protected $table = 'files';
    
    protected $fillable = [
        'uuid', 'user_id', 'user_package_id',
        'fileable_type', 'fileable_id',
        'file_category', 'file_type', 'mime_type',
        'disk', 'path', 'thumbnail_path', 'watermark_path',
        'original_name', 'file_name', 'size_bytes', 'optimized_size_bytes',
        'width', 'height', 'duration', 'exif_data',
        'is_optimized', 'optimization_ratio', 'optimization_status',
        'has_watermark', 'status', 'visibility',
        'download_count', 'view_count',
        'last_downloaded_at', 'last_viewed_at',
        'hash', 'access_token', 'expires_at'
    ];
    
    protected $casts = [
        'size_bytes' => 'integer',
        'optimized_size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'exif_data' => 'array',
        'optimization_ratio' => 'float',
        'is_optimized' => 'boolean',
        'has_watermark' => 'boolean',
        'download_count' => 'integer',
        'view_count' => 'integer',
        'last_downloaded_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($file) {
            if (empty($file->uuid)) {
                $file->uuid = (string) Str::uuid();
            }
            if (empty($file->access_token)) {
                $file->access_token = Str::random(64);
            }
        });
    }
    
    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function userPackage()
    {
        return $this->belongsTo(UserPackage::class, 'user_package_id');
    }
    
    public function fileable()
    {
        return $this->morphTo();
    }
    
    public function propertyRelation()
    {
        return $this->hasOne(PropertyFileRelation::class);
    }
    
    public function projectRelation()
    {
        return $this->hasOne(ProjectFile::class);
    }
    
    public function versions()
    {
        return $this->hasMany(FileVersion::class);
    }
    
    public function conversions()
    {
        return $this->hasMany(FileConversion::class);
    }
    
    public function usageLogs()
    {
        return $this->hasMany(FileUsageLog::class);
    }
    
    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }
    
    public function scopeByCategory($query, $category)
    {
        return $query->where('file_category', $category);
    }
    
    /**
     * Accessors
     */
    public function getUrlAttribute(): string
    {
        if ($this->visibility === 'private') {
            return route('files.private', ['token' => $this->access_token]);
        }
        
        return asset('storage/' . $this->path);
    }
    
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }
        
        return null;
    }
    
    public function getSizeFormattedAttribute(): string
    {
        return $this->formatBytes($this->size_bytes);
    }
    
    public function getOptimizedSizeFormattedAttribute(): ?string
    {
        if ($this->optimized_size_bytes) {
            return $this->formatBytes($this->optimized_size_bytes);
        }
        return null;
    }
    
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Helper methods
     */
    public function incrementView(?int $userId = null, ?string $ip = null): void
    {
        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);
        
        $this->usageLogs()->create([
            'user_id' => $userId,
            'ip_address' => $ip ?? request()->ip(),
            'user_agent' => request()->userAgent(),
            'action' => 'view',
        ]);
    }
    
    public function incrementDownload(?int $userId = null, ?string $ip = null): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
        
        $this->usageLogs()->create([
            'user_id' => $userId,
            'ip_address' => $ip ?? request()->ip(),
            'user_agent' => request()->userAgent(),
            'action' => 'download',
        ]);
    }
    
    public function isImage(): bool
    {
        return $this->file_category === 'image';
    }
    
    public function isVideo(): bool
    {
        return $this->file_category === 'video';
    }
    
    public function isDocument(): bool
    {
        return $this->file_category === 'document';
    }
}