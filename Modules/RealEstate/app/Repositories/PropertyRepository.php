<?php
namespace Modules\RealEstate\Repositories;

use App\Repositories\CoreRepository;
use Modules\RealEstate\Models\Property;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class PropertyRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Property::class;
    }



    /**
     * Paginate properties with filters
     */
    public function paginate(array $filter): LengthAwarePaginator
    {

     
        $query = $this->model()->query();

        // Apply filters
        $query = $this->applyFilters($query, $filter);
        
        // Apply sorting
        $query = $this->applySorting($query, $filter);

        return $query->paginate(data_get($filter, 'per_page', 15));
    }

    /**
     * Apply all filters to query
     */
    protected function applyFilters(Builder $query, array $filter): Builder
    {
        // Search by keyword (multi-language)
        if ($keyword = data_get($filter, 'keyword')) {
            $query->where(function($q) use ($keyword) {
                // Search in main table
                $q->where('full_address', 'like', "%{$keyword}%")
                  ->orWhere('project_name', 'like', "%{$keyword}%");
                
                // Search in translations
                $q->orWhereHas('translations', function($t) use ($keyword) {
                    $t->where('title', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
                });
            });
        }

        // Category filter
        if ($categoryId = data_get($filter, 'category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Project filter
        if ($projectId = data_get($filter, 'project_id')) {
            $query->where('project_id', $projectId);
        }

        // User filter
        if ($userId = data_get($filter, 'user_id')) {
            $query->where('user_id', $userId);
        }

        // Location filters using hierarchy IDs
        if ($countryId = data_get($filter, 'country_id')) {
            $query->where('country_id', $countryId);
        }

        if ($provinceId = data_get($filter, 'province_id')) {
            $query->where('province_id', $provinceId);
        }

        if ($districtId = data_get($filter, 'district_id')) {
            $query->where('district_id', $districtId);
        }

        if ($wardId = data_get($filter, 'ward_id')) {
            $query->where('ward_id', $wardId);
        }

        // Price range
        if ($priceMin = data_get($filter, 'price_min')) {
            $query->where('price', '>=', $priceMin);
        }

        if ($priceMax = data_get($filter, 'price_max')) {
            $query->where('price', '<=', $priceMax);
        }

        // Area range
        if ($areaMin = data_get($filter, 'area_min')) {
            $query->where('area', '>=', $areaMin);
        }

        if ($areaMax = data_get($filter, 'area_max')) {
            $query->where('area', '<=', $areaMax);
        }

        // Bedrooms & Bathrooms
        if ($bedrooms = data_get($filter, 'bedrooms')) {
            $query->where('bedrooms', '>=', $bedrooms);
        }

        if ($bathrooms = data_get($filter, 'bathrooms')) {
            $query->where('bathrooms', '>=', $bathrooms);
        }

        // Other filters
        if ($furnishing = data_get($filter, 'furnishing')) {
            $query->where('furnishing', $furnishing);
        }

        if ($legalStatus = data_get($filter, 'legal_status')) {
            $query->where('legal_status', $legalStatus);
        }

        // Transaction type (sale/rent)
        if ($type = data_get($filter, 'type')) {
            $query->where('type', $type);
        }

        // Status filter
        if ($status = data_get($filter, 'status')) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['available', 'pending']);
        }

        // Special flags
        if (data_get($filter, 'is_vip')) {
            $query->where('is_vip', true);
        }

        if (data_get($filter, 'is_featured')) {
            $query->where('is_featured', true);
        }

        if (data_get($filter, 'is_urgent')) {
            $query->where('is_urgent', true);
        }

        if (data_get($filter, 'is_top')) {
            $query->where('is_top', true);
        }

        // Amenities filter (must have all selected amenities)
        if ($amenities = data_get($filter, 'amenities')) {
            $amenities = is_array($amenities) ? $amenities : explode(',', $amenities);
            $query->whereHas('amenities', function($q) use ($amenities) {
                $q->whereIn('amenities.id', $amenities);
            }, '=', count($amenities));
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting(Builder $query, array $filter): Builder
    {
        $sortBy = data_get($filter, 'sort_by', 'created_at');
        $sortOrder = data_get($filter, 'sort_order', 'desc');
      
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'area_asc':
                $query->orderBy('area', 'asc');
                break;
            case 'area_desc':
                $query->orderBy('area', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }

    /**
     * Get single property by ID with all relations
     */
    public function show(int $id): ?Property
    {
        return $this->model()
            ->with([
                'user',
                'project',
                'category.translations' => fn($q) => $q->where('locale', $this->language),
                'translations',
                'images',
                'amenities.translations' => fn($q) => $q->where('locale', $this->language),
                'reviews' => fn($q) => $q->with('user')->latest(),
            ])
            ->find($id);
    }

    /**
     * Get property by UUID
     */
    public function findByUuid(string $uuid): ?Property
    {
        return $this->model()
            ->with([
                'user',
                'project',
                'category.translations' => fn($q) => $q->where('locale', $this->language),
                'translations',
                'images',
                'amenities.translations' => fn($q) => $q->where('locale', $this->language),
                'reviews' => fn($q) => $q->with('user')->latest(),
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Get featured properties
     */
    public function getFeatured(int $limit = 10)
    {
        return $this->model()
            ->with(['primaryImage', 'category'])
            ->where('is_featured', true)
            ->where('status', 'available')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Get VIP properties
     */
    public function getVip(int $limit = 10)
    {
        return $this->model()
            ->with(['primaryImage', 'category'])
            ->where('is_vip', true)
            ->where('status', 'available')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Get similar properties
     */
    public function getSimilar(Property $property, int $limit = 6)
    {
        return $this->model()
            ->with(['primaryImage', 'category'])
            ->where('id', '!=', $property->id)
            ->where('status', 'available')
            ->where(function($q) use ($property) {
                $q->where('category_id', $property->category_id)
                  ->orWhere('province_id', $property->province_id)
                  ->orWhere('district_id', $property->district_id)
                  ->orWhereHas('translations', function($t) use ($property) {
                      $t->where('locale', $this->language)
                        ->where(function($sub) use ($property) {
                            $sub->where('title', 'like', "%{$property->title}%")
                                 ->orWhere('description', 'like', "%{$property->description}%");
                        });
                  });
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Get user properties
     */
    public function getUserProperties(int $userId, array $filter = []): LengthAwarePaginator
    {
        $query = $this->model()
            ->where('user_id', $userId)
            ->with(['primaryImage', 'category']);

        if ($status = data_get($filter, 'status')) {
            $query->where('status', $status);
        }

        if ($search = data_get($filter, 'search')) {
            $query->where(function($q) use ($search) {
                $q->whereHas('translations', function($t) use ($search) {
                    $t->where('title', 'like', "%{$search}%");
                })->orWhere('full_address', 'like', "%{$search}%");
            });
        }

        $sortBy = data_get($filter, 'sort_by', 'created_at');
        $sortOrder = data_get($filter, 'sort_order', 'desc');
      
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'area_asc':
                $query->orderBy('area', 'asc');
                break;
            case 'area_desc':
                $query->orderBy('area', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate(data_get($filter, 'per_page', 15));
    }

    /**
     * Increment views and log view
     */
    public function incrementViews(int $propertyId, ?int $userId = null, ?string $ip = null): void
    {
        DB::transaction(function() use ($propertyId, $userId, $ip) {
            $property = $this->model()->find($propertyId);
            
            if ($property) {
                $property->increment('views');
                
                if ($userId) {
                    $property->increment('unique_views');
                }

                // Log view details
                $property->views()->create([
                    'user_id' => $userId,
                    'ip_address' => $ip ?? request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'device' => $this->getDevice(),
                    'platform' => $this->getPlatform(),
                    'browser' => $this->getBrowser(),
                    'viewed_at' => now()
                ]);
            }
        });
    }

    /**
     * Get properties for map view
     */
    public function getForMap(array $filter = [])
    {
        $query = $this->model()
            ->select(['id', 'uuid', 'title', 'latitude', 'longitude', 'price', 'address'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 'available');

        if ($provinceId = data_get($filter, 'province_id')) {
            $query->where('province_id', $provinceId);
        }

        if ($districtId = data_get($filter, 'district_id')) {
            $query->where('district_id', $districtId);
        }

        if ($categoryId = data_get($filter, 'category_id')) {
            $query->where('category_id', $categoryId);
        }

        return $query->limit(500)->get();
    }

    /**
     * Get property statistics
     */
    public function getStats(int $userId): array
    {
        $query = $this->model()->where('user_id', $userId);
        
        return [
            'total' => (clone $query)->count(),
            'published' => (clone $query)->where('status', 'available')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'sold' => (clone $query)->where('status', 'sold')->count(),
            'rented' => (clone $query)->where('status', 'rented')->count(),
            'expired' => (clone $query)->where('status', 'expired')->count(),
            'total_views' => (clone $query)->sum('views'),
            'unique_views' => (clone $query)->sum('unique_views'),
            'avg_price' => (clone $query)->avg('price'),
            'min_price' => (clone $query)->min('price'),
            'max_price' => (clone $query)->max('price'),
        ];
    }

    /**
     * Device detection methods
     */
    private function getDevice(): string
    {
        $agent = request()->userAgent();
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|tablet)))/i', $agent)) {
            return 'tablet';
        }
        if (preg_match('/(mobile|iphone|ipod|android|blackberry|windows phone)/i', $agent)) {
            return 'mobile';
        }
        return 'desktop';
    }

    private function getPlatform(): string
    {
        $agent = request()->userAgent();
        if (strpos($agent, 'Windows') !== false) return 'windows';
        if (strpos($agent, 'Mac') !== false) return 'macos';
        if (strpos($agent, 'Linux') !== false) return 'linux';
        if (strpos($agent, 'Android') !== false) return 'android';
        if (strpos($agent, 'iOS') !== false) return 'ios';
        return 'unknown';
    }

    private function getBrowser(): string
    {
        $agent = request()->userAgent();
        if (strpos($agent, 'Chrome') !== false) return 'chrome';
        if (strpos($agent, 'Firefox') !== false) return 'firefox';
        if (strpos($agent, 'Safari') !== false) return 'safari';
        if (strpos($agent, 'Edge') !== false) return 'edge';
        if (strpos($agent, 'Opera') !== false) return 'opera';
        return 'other';
    }
}