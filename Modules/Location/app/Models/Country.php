<?php
namespace Modules\Location\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class Country extends Model implements TranslatableContract
{
    use Translatable;

    public $guarded = ['id'];
    public $timestamps = false;
    
    public $translatedAttributes = ['name', 'native_name'];
    
    protected $casts = [
        'active' => 'bool',
        'is_default' => 'bool',
    ];
    
    /**
     * Relationships
     */
    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }
    
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }
    
    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class);
    }
    
    /**
     * Accessors
     */
    public function getNameAttribute(): ?string
    {
        return $this->translateOrDefault(app()->getLocale())?->name ?? $this->code;
    }
    
    public function getNativeNameAttribute(): ?string
    {
        return $this->translateOrDefault(app()->getLocale())?->native_name ?? $this->code;
    }
    
    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
    
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
    
    public function scopeFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when(data_get($filter, 'code'), fn($q, $code) => $q->where('code', $code))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(isset($filter['is_default']), fn($q) => $q->where('is_default', $filter['is_default']))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('code', 'LIKE', "%$search%")
                      ->orWhereHas('translations', function($t) use ($search) {
                          $t->where('name', 'LIKE', "%$search%")
                            ->orWhere('native_name', 'LIKE', "%$search%");
                      });
                });
            })
            ->when(data_get($filter, 'lang'), function ($query, $locale) {
                $query->with('translations', function($q) use ($locale) {
                    $q->where('locale', $locale);
                });
            });
    }
    
    /**
     * Helper methods
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->first();
    }
    
    public static function getByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }
}