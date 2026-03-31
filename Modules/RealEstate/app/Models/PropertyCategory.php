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
        'image',
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
                $englishTranslation = $category->translateOrDefault('en');
                $category->slug = Str::slug($englishTranslation->name);
            }
        });

        static::updating(function ($category) {
            // Only update slug if name changed and slug hasn't been manually set
            if ($category->isDirty('slug')) return;
            
            $englishTranslation = $category->translateOrDefault('en');
            $originalEnglishTranslation = $category->getOriginal('translations')['en'] ?? null;
            
            if ($originalEnglishTranslation && $englishTranslation->name !== $originalEnglishTranslation['name']) {
                $category->slug = Str::slug($englishTranslation->name);
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