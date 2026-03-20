<?php
namespace Modules\RealEstate\Repositories;

use Modules\RealEstate\Models\Property;
use Illuminate\Support\Facades\DB;

class PropertyRepository
{
    public function __construct(
        protected Property $model
    ) {}

    public function paginate(array $filter)
    {
        $query = $this->model->with([
            'user',
            'project',
            'category',
            'type',
            'primaryImage',
            'amenities'
        ]);

        if (data_get($filter, 'keyword')) {
            $keyword = $filter['keyword'];
            $query->where(function($q) use ($keyword) {
                $q->where('title->en', 'like', "%{$keyword}%")
                  ->orWhere('title->vi', 'like', "%{$keyword}%")
                  ->orWhere('description->en', 'like', "%{$keyword}%")
                  ->orWhere('description->vi', 'like', "%{$keyword}%")
                  ->orWhere('address', 'like', "%{$keyword}%")
                  ->orWhere('project_name', 'like', "%{$keyword}%");
            });
        }

        if (data_get($filter, 'category_id')) {
            $query->where('category_id', $filter['category_id']);
        }

        if (data_get($filter, 'type_id')) {
            $query->where('type_id', $filter['type_id']);
        }

        if (data_get($filter, 'project_id')) {
            $query->where('project_id', $filter['project_id']);
        }

        if (data_get($filter, 'user_id')) {
            $query->where('user_id', $filter['user_id']);
        }

        if (data_get($filter, 'city')) {
            $query->where('city', $filter['city']);
        }

        if (data_get($filter, 'district')) {
            $query->where('district', $filter['district']);
        }

        if (data_get($filter, 'price_min')) {
            $query->where('price', '>=', $filter['price_min']);
        }

        if (data_get($filter, 'price_max')) {
            $query->where('price', '<=', $filter['price_max']);
        }

        if (data_get($filter, 'area_min')) {
            $query->where('area', '>=', $filter['area_min']);
        }

        if (data_get($filter, 'area_max')) {
            $query->where('area', '<=', $filter['area_max']);
        }

        if (data_get($filter, 'bedrooms')) {
            $query->where('bedrooms', '>=', $filter['bedrooms']);
        }

        if (data_get($filter, 'bathrooms')) {
            $query->where('bathrooms', '>=', $filter['bathrooms']);
        }

        if (data_get($filter, 'furnishing')) {
            $query->where('furnishing', $filter['furnishing']);
        }

        if (data_get($filter, 'transaction_type')) {
            $query->where('transaction_type', $filter['transaction_type']);
        }

        if (data_get($filter, 'status')) {
            $query->where('status', $filter['status']);
        } else {
            $query->whereIn('status', ['available', 'pending']);
        }

        if (data_get($filter, 'is_vip')) {
            $query->vip();
        }

        if (data_get($filter, 'is_featured')) {
            $query->where('is_featured', true);
        }

        if (data_get($filter, 'amenities')) {
            $amenities = $filter['amenities'];
            $query->whereHas('amenities', function($q) use ($amenities) {
                $q->whereIn('amenities.id', $amenities);
            }, '=', count($amenities));
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
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate(data_get($filter, 'per_page', 15));
    }

    public function show(int $id): ?Property
    {
        return $this->model
            ->with([
                'user',
                'project',
                'category',
                'type',
                'images',
                'amenities',
                'assignedAgents',
                'primaryAgent'
            ])
            ->find($id);
    }

    public function findByUuid(string $uuid): ?Property
    {
        return $this->model
            ->with([
                'user',
                'project',
                'category',
                'type',
                'images',
                'amenities',
                'assignedAgents',
                'primaryAgent'
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    public function getFeatured(int $limit = 10)
    {
        return $this->model
            ->with(['primaryImage', 'category', 'type'])
            ->where('is_featured', true)
            ->where('status', 'available')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getSimilar(Property $property, int $limit = 6)
    {
        return $this->model
            ->where('id', '!=', $property->id)
            ->where('status', 'available')
            ->where(function($q) use ($property) {
                $q->where('category_id', $property->category_id)
                  ->orWhere('city', $property->city)
                  ->orWhere('district', $property->district);
            })
            ->with(['primaryImage', 'category', 'type'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getUserProperties(int $userId, array $filter = [])
    {
        $query = $this->model
            ->where('user_id', $userId)
            ->with(['primaryImage', 'category', 'type']);

        if (data_get($filter, 'status')) {
            $query->where('status', $filter['status']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(data_get($filter, 'per_page', 15));
    }

    public function incrementViews(int $propertyId, ?int $userId = null, ?string $ip = null): void
    {
        DB::transaction(function() use ($propertyId, $userId, $ip) {
            $property = $this->model->find($propertyId);
            
            if ($property) {
                $property->increment('views');
                
                if ($userId) {
                    $property->increment('unique_views');
                }

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