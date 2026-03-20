<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Str;

// use Modules\RealEstate\Database\Factories\PropertyCategoryFactory;

class PropertyCategory extends Model
{
    use SoftDeletes;

    protected $table = 'property_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'parent_id',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'name' => 'array',
        'description' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->getTranslation('name', 'en'));
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = Str::slug($category->getTranslation('name', 'en'));
            }
        });
    }

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

    public function getTranslation(string $field, string $locale)
    {
        return $this->{$field}[$locale] ?? $this->{$field}['en'] ?? '';
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}