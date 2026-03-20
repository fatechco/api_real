<?php
namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class FileCompressionJob extends Model
{
    protected $table = 'file_compression_jobs';
    
    protected $fillable = [
        'file_id',
        'status',
        'error_message',
        'attempts',
        'processed_at'
    ];
    
    protected $casts = [
        'attempts' => 'integer',
        'processed_at' => 'datetime',
    ];
    
    /**
     * Relationships
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }
    
    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }
    
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
    
    /**
     * Helper methods
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }
    
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }
    
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'processed_at' => now(),
        ]);
    }
    
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
    
    public function canRetry(): bool
    {
        return $this->attempts < 3 && $this->status === 'failed';
    }
}