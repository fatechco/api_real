<?php
namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\User\Models\User;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'developer',
        'agency_id',
        'developer_info',
        'address',
        'city',
        'district',
        'ward',
        'latitude',
        'longitude',
        'total_area',
        'total_units',
        'start_date',
        'completion_date',
        'status',
        'images',
        'virtual_tour',
        'brochure_url',
        'video_url',
        'is_featured',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keywords'
    ];

    protected $casts = [
        'total_area' => 'decimal:2',
        'total_units' => 'integer',
        'start_date' => 'date',
        'completion_date' => 'date',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'images' => 'array',
        'developer_info' => 'array',
        'virtual_tour' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'name' => 'array',
        'description' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'meta_keywords' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->getTranslation('name', 'en'));
            }
        });
    }

    public function getTranslation(string $field, string $locale)
    {
        return $this->{$field}[$locale] ?? $this->{$field}['en'] ?? '';
    }

    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'project_amenities')
                    ->withPivot(['value', 'description', 'icon', 'is_highlight', 'order'])
                    ->withTimestamps()
                    ->orderByPivot('order');
    }

    public function highlightedAmenities()
    {
        return $this->belongsToMany(Amenity::class, 'project_amenities')
                    ->wherePivot('is_highlight', true)
                    ->withPivot('value', 'description', 'icon')
                    ->orderByPivot('order');
    }

    public function favoritedBy()
    {
        return $this->morphToMany(User::class, 'favorable', 'favorites');
    }

    public function getPrimaryImageAttribute(): ?string
    {
        $images = $this->images ?? [];
        return $images[0] ?? null;
    }

    public function getAllImagesAttribute(): array
    {
        $images = $this->images ?? [];
        return array_map(function($image) {
            return asset('storage/' . $image);
        }, $images);
    }

    public function getPropertyCountAttribute(): int
    {
        return $this->properties()->count();
    }

    public function getAvailablePropertyCountAttribute(): int
    {
        return $this->properties()->where('status', 'available')->count();
    }

    public function getPriceRangeAttribute(): array
    {
        $prices = $this->properties()
            ->where('status', 'available')
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        return [
            'min' => $prices->min_price ?? 0,
            'min_formatted' => $prices->min_price ? number_format($prices->min_price) . ' ₫' : null,
            'max' => $prices->max_price ?? 0,
            'max_formatted' => $prices->max_price ? number_format($prices->max_price) . ' ₫' : null
        ];
    }

    public function getAreaRangeAttribute(): array
    {
        $areas = $this->properties()
            ->where('status', 'available')
            ->selectRaw('MIN(area) as min_area, MAX(area) as max_area')
            ->first();

        return [
            'min' => $areas->min_area ?? 0,
            'min_formatted' => $areas->min_area ? number_format($areas->min_area, 2) . ' m²' : null,
            'max' => $areas->max_area ?? 0,
            'max_formatted' => $areas->max_area ? number_format($areas->max_area, 2) . ' m²' : null
        ];
    }

    public function getBedroomTypesAttribute(): array
    {
        return $this->properties()
            ->where('status', 'available')
            ->whereNotNull('bedrooms')
            ->distinct()
            ->orderBy('bedrooms')
            ->pluck('bedrooms')
            ->map(function($bedrooms) {
                return [
                    'value' => $bedrooms,
                    'label' => $bedrooms . ' BR',
                    'count' => $this->properties()
                        ->where('status', 'available')
                        ->where('bedrooms', $bedrooms)
                        ->count()
                ];
            })
            ->toArray();
    }

    public function getAmenitiesGroupedAttribute(): array
    {
        $amenities = $this->amenities()
            ->withPivot('description', 'icon', 'is_highlight')
            ->get()
            ->groupBy('category');

        $result = [];
        foreach ($amenities as $category => $items) {
            $result[$category] = [
                'category_name' => Amenity::getCategories()[$category] ?? $category,
                'items' => $items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'icon' => $item->pivot->icon ?? $item->icon,
                        'description' => $item->pivot->description,
                        'value' => $item->pivot->value,
                        'is_highlight' => $item->pivot->is_highlight
                    ];
                })
            ];
        }

        return $result;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'planning' => 'Planning',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            default => $this->status
        };
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getProgressAttribute(): int
    {
        if ($this->status === 'completed') {
            return 100;
        }

        if (!$this->start_date || !$this->completion_date) {
            return 0;
        }

        $total = $this->start_date->diffInDays($this->completion_date);
        $elapsed = $this->start_date->diffInDays(now());

        if ($total <= 0) {
            return 0;
        }

        return min(100, round(($elapsed / $total) * 100));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDeveloper($query, string $developer)
    {
        return $query->where('developer', 'like', "%{$developer}%");
    }

    public function scopeWithAmenities($query, array $amenityIds)
    {
        return $query->whereHas('amenities', function($q) use ($amenityIds) {
            $q->whereIn('amenities.id', $amenityIds);
        });
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 5)
    {
        $haversine = "(6371 * acos(cos(radians($latitude)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians($longitude)) 
                    + sin(radians($latitude)) 
                    * sin(radians(latitude))))";

        return $query->select('*')
            ->selectRaw("{$haversine} AS distance")
            ->whereRaw("{$haversine} <= ?", [$radius])
            ->orderBy('distance');
    }
}