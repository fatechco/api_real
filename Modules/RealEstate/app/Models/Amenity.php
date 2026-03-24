<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class Amenity extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable;

    protected $table = 'amenities';

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'slug',
        'icon',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($amenity) {
            if (empty($amenity->slug)) {
                $amenity->slug = Str::slug($amenity->translateOrDefault('en')->name);
            }
        });
    }

    /**
     * Relationships
     */
    public function properties()
    {
        return $this->belongsToMany(Property::class, 'property_amenities')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_amenities');
    }

    /**
     * Accessors
     */
    public function getNameAttribute(): ?string
    {
        return $this->translateOrDefault(app()->getLocale())?->name;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->translateOrDefault(app()->getLocale())?->description;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}