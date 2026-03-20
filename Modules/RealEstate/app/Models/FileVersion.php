<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    protected $table = 'file_versions';
    
    protected $fillable = [
        'file_id',
        'version_number',
        'path',
        'size_bytes',
        'changes',
        'metadata'
    ];
    
    protected $casts = [
        'size_bytes' => 'integer',
        'version_number' => 'integer',
        'metadata' => 'array',
    ];
    
    /**
     * Relationships
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }
    
    /**
     * Accessors
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
    
    public function getSizeFormattedAttribute(): string
    {
        return $this->formatBytes($this->size_bytes);
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
}