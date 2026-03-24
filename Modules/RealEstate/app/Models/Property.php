<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Modules\Location\Models\Country;
use Modules\Location\Models\Province;
use Modules\Location\Models\District;
use Modules\Location\Models\Ward;

class Property extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable;

    protected $table = 'properties';

    public $translatedAttributes = ['title', 'description'];

    protected $fillable = [
        'uuid',
        'user_id',
        'project_id',
        'category_id',
        'slug',
        'price',
        'price_per_m2',
        'is_negotiable',
        'area',
        'land_area',
        'built_area',
        'bedrooms',
        'bathrooms',
        'floors',
        'garages',
        'year_built',
        'furnishing',
        'legal_status',
        'ownership_type',
        'country_id',
        'province_id',
        'district_id',
        'ward_id',
        'street',
        'street_number',
        'building_name',
        'full_address',
        'latitude',
        'longitude',
        'project_name',
        'status',
        'type',
        'is_featured',
        'is_vip',
        'vip_expires_at',
        'is_urgent',
        'is_top',
        'top_expires_at',
        'views',
        'unique_views',
        'contact_views',
        'favorites_count',
        'published_at',
        'expired_at',
        'primary_image_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_per_m2' => 'decimal:2',
        'area' => 'decimal:2',
        'land_area' => 'decimal:2',
        'built_area' => 'decimal:2',
        'is_negotiable' => 'boolean',
        'is_featured' => 'boolean',
        'is_vip' => 'boolean',
        'is_urgent' => 'boolean',
        'is_top' => 'boolean',
        'views' => 'integer',
        'unique_views' => 'integer',
        'contact_views' => 'integer',
        'favorites_count' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'vip_expires_at' => 'datetime',
        'top_expires_at' => 'datetime',
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($property) {
            if (empty($property->uuid)) {
                $property->uuid = (string) Str::uuid();
            }
            if (empty($property->slug)) {
                $property->slug = Str::slug($property->translateOrDefault('en')->title);
            }
        });

        static::updating(function ($property) {
            if ($property->isDirty('slug')) return;
            
            $originalTitle = $property->getOriginal('title');
            $newTitle = $property->title;
            
            if ($originalTitle !== $newTitle) {
                $property->slug = Str::slug($property->translateOrDefault('en')->title);
            }
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function category()
    {
        return $this->belongsTo(PropertyCategory::class, 'category_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function primaryImage()
    {
        return $this->belongsTo(File::class, 'primary_image_id');
    }

    public function images()
    {
        return $this->hasMany(File::class, 'fileable_id')
            ->where('fileable_type', self::class)
            ->orderBy('order');
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'property_amenities')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    public function views()
    {
        return $this->hasMany(PropertyView::class);
    }

    /*public function reviews()
    {
        return $this->hasMany(PropertyReview::class);
    }

    public function favorites()
    {
        return $this->morphMany(\App\Models\Favorite::class, 'favorable');
    }*/

    /**
     * Accessors
     */
    public function getTitleAttribute(): ?string
    {
        return $this->translateOrDefault(app()->getLocale())?->title;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->translateOrDefault(app()->getLocale())?->description;
    }

    public function getPriceFormattedAttribute(): string
    {
        if ($this->type === 'rent') {
            return '$' . number_format($this->price) . ' / month';
        }
        return '$' . number_format($this->price);
    }

    public function getAreaFormattedAttribute(): string
    {
        return number_format($this->area, 2) . ' m²';
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street_number,
            $this->street,
            $this->ward?->name,
            $this->district?->name,
            $this->province?->name,
            $this->country?->name,
        ]);
        
        return implode(', ', $parts);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Pending',
            'available' => 'Available',
            'sold' => 'Sold',
            'rented' => 'Rented',
            'expired' => 'Expired',
            'hidden' => 'Hidden',
            'rejected' => 'Rejected',
        ];
        
        return $labels[$this->status] ?? $this->status;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'sale' ? 'For Sale' : 'For Rent';
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVip($query)
    {
        return $query->where('is_vip', true)
            ->where(function($q) {
                $q->whereNull('vip_expires_at')
                  ->orWhere('vip_expires_at', '>', now());
            });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCity($query, $cityId)
    {
        return $query->where('province_id', $cityId);
    }

    public function scopeByDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    public function scopeAreaRange($query, $min, $max)
    {
        return $query->whereBetween('area', [$min, $max]);
    }

    /**
     * Helper methods
     */
    public function incrementViews(?int $userId = null, ?string $ip = null): void
    {
        $this->increment('views');
        
        if ($userId) {
            $this->increment('unique_views');
        }

        $this->views()->create([
            'user_id' => $userId,
            'ip_address' => $ip ?? request()->ip(),
            'user_agent' => request()->userAgent(),
            'viewed_at' => now(),
        ]);
    }

    public function canEdit(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}