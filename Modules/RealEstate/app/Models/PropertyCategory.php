<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class PropertyCategory extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable;

    protected $table = 'property_categories';

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'slug',
        'icon',
        'parent_id',
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

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->translateOrDefault('en')->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('slug')) return;
            
            $originalName = $category->getOriginal('name');
            $newName = $category->name;
            
            if ($originalName !== $newName) {
                $category->slug = Str::slug($category->translateOrDefault('en')->name);
            }
        });
    }

    /**
     * Relationships
     */
    public function parent()
    {
        return $this->belongsTo(PropertyCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PropertyCategory::class, 'parent_id');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'category_id');
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

    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_name . ' > ' . $this->name;
        }
        return $this->name;
    }

    public function getPropertyCountAttribute(): int
    {
        return $this->properties()->count();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}