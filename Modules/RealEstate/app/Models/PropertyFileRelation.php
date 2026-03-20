<?php
namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyFileRelation extends Model
{
    protected $table = 'property_file_relations';
    
    protected $fillable = [
        'property_id', 'file_id', 'order', 'is_primary', 'is_featured',
        'usage_type', 'caption', 'description', 'metadata'
    ];
    
    protected $casts = [
        'order' => 'integer',
        'is_primary' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];
    
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
    
    public function file()
    {
        return $this->belongsTo(File::class);
    }
    
    /**
     * Scope for primary image
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
    
    /**
     * Scope by usage type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('usage_type', $type);
    }
}