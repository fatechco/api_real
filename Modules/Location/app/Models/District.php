<?php
namespace Modules\Location\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class District extends Model implements TranslatableContract
{
    use Translatable;

    public $guarded = ['id'];
    public $timestamps = false;
    
    public $translatedAttributes = ['name'];
    
    protected $casts = [
        'active' => 'bool',
    ];
    
    /**
     * Relationships
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
    
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
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
    
    public function getTypeLabelAttribute(): string
    {
        $types = [
            'district' => trans('location.district'),
            'county' => trans('location.county'),
            'city' => trans('location.city'),
        ];
        return $types[$this->type] ?? $this->type;
    }
    
    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
    
    public function scopeByProvince(Builder $query, $provinceId): Builder
    {
        return $query->where('province_id', $provinceId);
    }
    
    public function scopeFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when(data_get($filter, 'province_id'), fn($q, $id) => $q->where('province_id', $id))
            ->when(data_get($filter, 'country_id'), fn($q, $id) => $q->where('country_id', $id))
            ->when(data_get($filter, 'code'), fn($q, $code) => $q->where('code', $code))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'type'), fn($q, $type) => $q->where('type', $type))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('code', 'LIKE', "%$search%")
                      ->orWhereHas('translations', function($t) use ($search) {
                          $t->where('name', 'LIKE', "%$search%");
                      });
                });
            })
            ->when(data_get($filter, 'lang'), function ($query, $locale) {
                $query->with('translations', function($q) use ($locale) {
                    $q->where('locale', $locale);
                });
            });
    }
}