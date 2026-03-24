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
use Modules\User\Models\User;

class Project extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable;

    protected $table = 'projects';

    public $translatedAttributes = ['name', 'description', 'developer_name'];

    protected $fillable = [
        'uuid',
        'slug',
        'agency_id',
        'developer_id',
        'country_id',
        'province_id',
        'district_id',
        'ward_id',
        'address',
        'street',
        'street_number',
        'building_name',
        'latitude',
        'longitude',
        'total_area',
        'built_area',
        'total_units',
        'available_units',
        'total_floors',
        'basement_floors',
        'min_price',
        'max_price',
        'price_per_m2',
        'start_date',
        'completion_date',
        'handover_date',
        'status',
        'is_featured',
        'is_hot',
        'is_active',
        'views',
        'unique_views',
        'favorites_count',
        'inquiries_count',
        'meta_title',
        'meta_description',
        'meta_keywords'
    ];

    protected $casts = [
        'total_area' => 'decimal:2',
        'built_area' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'price_per_m2' => 'decimal:2',
        'total_units' => 'integer',
        'available_units' => 'integer',
        'total_floors' => 'integer',
        'basement_floors' => 'integer',
        'views' => 'integer',
        'unique_views' => 'integer',
        'favorites_count' => 'integer',
        'inquiries_count' => 'integer',
        'is_featured' => 'boolean',
        'is_hot' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'start_date' => 'date',
        'completion_date' => 'date',
        'handover_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->uuid)) {
                $project->uuid = (string) Str::uuid();
            }
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->translateOrDefault('en')->name);
            }
        });
    }

    /**
     * Relationships
     */
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function developer()
    {
        return $this->belongsTo(User::class, 'developer_id');
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

    public function images()
    {
        return $this->hasMany(File::class, 'fileable_id')
            ->where('fileable_type', self::class)
            ->orderBy('order');
    }

    /*public function units()
    {
        return $this->hasMany(ProjectUnit::class);
    }*/

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'project_amenities');
    }

    /*public function reviews()
    {
        return $this->hasMany(ProjectReview::class);
    }*/

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

    public function getDeveloperNameAttribute(): ?string
    {
        return $this->translateOrDefault(app()->getLocale())?->developer_name;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->ward?->name,
            $this->district?->name,
            $this->province?->name,
            $this->country?->name,
        ]);
        
        return implode(', ', $parts);
    }

    public function getPriceRangeAttribute(): string
    {
        if ($this->min_price && $this->max_price) {
            return '$' . number_format($this->min_price) . ' - $' . number_format($this->max_price);
        }
        
        if ($this->min_price) {
            return 'From $' . number_format($this->min_price);
        }
        
        if ($this->max_price) {
            return 'Up to $' . number_format($this->max_price);
        }
        
        return 'Contact for price';
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'planning' => 'Planning',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            'sold_out' => 'Sold Out',
            'paused' => 'Paused',
            'cancelled' => 'Cancelled',
        ];
        
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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
    }
}