<?php
namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    protected $table = 'project_files';
    
    protected $fillable = [
        'project_id',
        'file_id',
        'order',
        'usage_type',
        'caption',
        'metadata'
    ];
    
    protected $casts = [
        'order' => 'integer',
        'metadata' => 'array',
    ];
    
    /**
     * Relationships
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function file()
    {
        return $this->belongsTo(File::class);
    }
    
    /**
     * Scopes
     */
    public function scopeByType($query, $type)
    {
        return $query->where('usage_type', $type);
    }
    
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}