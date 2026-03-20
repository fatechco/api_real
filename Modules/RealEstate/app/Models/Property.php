<?php
namespace Modules\RealEstate\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\User\Models\User;

class Property extends Model
{
    use SoftDeletes;

    protected $table = 'properties';

    protected $fillable = [
        'uuid',
        'user_id',
        'project_id',
        'category_id',
        'type_id',
        'title',
        'slug',
        'description',
        'content',
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
        'address',
        'city',
        'district',
        'ward',
        'street',
        'project_name',
        'latitude',
        'longitude',
        'map_url',
        'status',
        'transaction_type',
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
        'meta_title',
        'meta_description',
        'meta_keywords',
        'published_at',
        'expired_at'
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
        'vip_expires_at' => 'datetime',
        'top_expires_at' => 'datetime',
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'views' => 'integer',
        'unique_views' => 'integer',
        'contact_views' => 'integer',
        'favorites_count' => 'integer',
        'title' => 'array',
        'description' => 'array',
        'content' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'meta_keywords' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($property) {
            if (empty($property->uuid)) {
                $property->uuid = (string) Str::uuid();
            }
            if (empty($property->slug)) {
                $property->slug = Str::slug($property->getTranslation('title', 'en'));
            }
            if (empty($property->published_at) && $property->status === 'available') {
                $property->published_at = now();
            }
        });

        static::updating(function ($property) {
            if ($property->isDirty('title') && !$property->isDirty('slug')) {
                $property->slug = Str::slug($property->getTranslation('title', 'en'));
            }
            if ($property->isDirty('status') && $property->status === 'available' && empty($property->published_at)) {
                $property->published_at = now();
            }
        });
    }

    public function getTranslation(string $field, string $locale)
    {
        return $this->{$field}[$locale] ?? $this->{$field}['en'] ?? '';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function category()
    {
        return $this->belongsTo(PropertyCategory::class, 'category_id');
    }

    public function type()
    {
        return $this->belongsTo(PropertyType::class, 'type_id');
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class)->orderBy('order');
    }

    public function primaryImage()
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
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

    public function favoritedBy()
    {
        return $this->morphToMany(User::class, 'favorable', 'favorites');
    }

    public function assignedAgents()
    {
        return $this->belongsToMany(User::class, 'agent_property_assignments')
                    ->withPivot('type', 'commission_rate', 'assigned_at', 'approved_at')
                    ->withTimestamps();
    }

    public function primaryAgent()
    {
        return $this->belongsToMany(User::class, 'agent_property_assignments')
                    ->wherePivot('type', 'primary')
                    ->withPivot('commission_rate', 'assigned_at');
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $image = $this->primaryImage;
        return $image ? asset('storage/' . $image->path) : null;
    }

    public function getAllImagesAttribute(): array
    {
        return $this->images->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => asset('storage/' . $image->path),
                'thumbnail' => asset('storage/' . $image->thumbnail_path),
                'caption' => $image->caption,
                'is_primary' => $image->is_primary
            ];
        })->toArray();
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->ward,
            $this->district,
            $this->city
        ]);
        return implode(', ', $parts);
    }

    public function getPriceFormattedAttribute(): string
    {
        return number_format($this->price) . ' ₫';
    }

    public function getAreaFormattedAttribute(): string
    {
        return number_format($this->area, 2) . ' m²';
    }

    public function getIsVipActiveAttribute(): bool
    {
        return $this->is_vip && $this->vip_expires_at && $this->vip_expires_at->isFuture();
    }

    public function getIsTopActiveAttribute(): bool
    {
        return $this->is_top && $this->top_expires_at && $this->top_expires_at->isFuture();
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'available' => 'Available',
            'sold' => 'Sold',
            'rented' => 'Rented',
            'expired' => 'Expired',
            'hidden' => 'Hidden',
            'rejected' => 'Rejected',
            default => $this->status
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeVip($query)
    {
        return $query->where('is_vip', true)
                     ->where('vip_expires_at', '>', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByDistrict($query, string $district)
    {
        return $query->where('district', $district);
    }

    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    public function scopeByAreaRange($query, $min, $max)
    {
        return $query->whereBetween('area', [$min, $max]);
    }

    public function scopeByBedrooms($query, int $bedrooms)
    {
        return $query->where('bedrooms', '>=', $bedrooms);
    }

    public function scopeByTransactionType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

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
            'viewed_at' => now()
        ]);
    }

    public function isOwner(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    public function canEdit(int $userId): bool
    {
        if ($this->isOwner($userId)) {
            return true;
        }

        $user = User::find($userId);
        return $user && ($user->hasRole('admin') || $user->hasRole('agency'));
    }

    public function getRemainingVipDays(): ?int
    {
        if (!$this->is_vip || !$this->vip_expires_at) {
            return null;
        }
        return max(0, now()->diffInDays($this->vip_expires_at, false));
    }

    public function getSimilarProperties(int $limit = 6)
    {
        return self::where('id', '!=', $this->id)
            ->where('status', 'available')
            ->where(function($q) {
                $q->where('category_id', $this->category_id)
                  ->orWhere('city', $this->city);
            })
            ->with(['primaryImage', 'category', 'type'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}