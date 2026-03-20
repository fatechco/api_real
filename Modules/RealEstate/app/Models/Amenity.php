<?php
namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Amenity extends Model
{
    use SoftDeletes;

    protected $table = 'amenities';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
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

        static::creating(function ($amenity) {
            if (empty($amenity->slug)) {
                $amenity->slug = Str::slug($amenity->name);
            }
        });
    }

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'property_amenities')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_amenities')
                    ->withPivot(['value', 'description', 'icon', 'is_highlight', 'order'])
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

}