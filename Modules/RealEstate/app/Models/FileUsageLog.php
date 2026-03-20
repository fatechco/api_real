<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Models\User;

class FileUsageLog extends Model
{
    protected $table = 'file_usage_logs';
    
    protected $fillable = [
        'file_id',
        'user_id',
        'ip_address',
        'user_agent',
        'action',
        'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array',
    ];
    
    /**
     * Relationships
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scopes
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
    
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
    
    public function scopeLastDays($query, $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    
    /**
     * Accessors
     */
    public function getActionLabelAttribute(): string
    {
        $labels = [
            'view' => 'View',
            'download' => 'Download',
            'share' => 'Share',
            'embed' => 'Embed',
        ];
        
        return $labels[$this->action] ?? $this->action;
    }
}