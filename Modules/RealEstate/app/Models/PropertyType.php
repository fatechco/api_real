<?php
namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PropertyType extends Model
{
    use SoftDeletes;

    protected $table = 'property_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
        'order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($type) {
            if (empty($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}