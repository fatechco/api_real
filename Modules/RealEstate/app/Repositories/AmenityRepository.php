<?php
namespace Modules\RealEstate\Repositories;

use App\Repositories\CoreRepository;
use Modules\RealEstate\Models\Amenity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AmenityRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Amenity::class;
    }
    
    public function paginate(array $filter): LengthAwarePaginator
    {
        $query = $this->model()->with(['translations']);
        
        $this->applyFilters($query, $filter);
        $this->applySearch($query, $filter);
        
        return $query->orderBy('order')
            ->paginate($filter['per_page'] ?? 15);
    }
    
    public function getPopular(int $limit = 12): Collection
    {
        return $this->model()
            ->with(['translations'])
            ->where('is_active', true)
            ->orderBy('order')
            ->limit($limit)
            ->get();
    }
     
    public function search(string $keyword, array $filter = []): LengthAwarePaginator
    {
        $query = $this->model()->with(['translations']);
        
        $query->whereHas('translations', function($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%");
        });
        
        return $query->paginate($filter['per_page'] ?? 15);
    }
    
    public function findBySlug(string $slug): ?Amenity
    {
        return $this->model()
            ->with(['translations'])
            ->where('slug', $slug)
            ->first();
    }
    
    public function getProperties(int $amenityId, array $filter = [])
    {
        $amenity = $this->model()->find($amenityId);
        
        if (!$amenity) {
            return [];
        }
        
        $query = $amenity->properties()
            ->with(['translations', 'primaryImage', 'category'])
            ->where('status', 'available');
        
        if ($search = data_get($filter, 'search')) {
            $query->whereHas('translations', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }
        
        return $query->paginate($filter['per_page'] ?? 15);
    }
    
    protected function applyFilters(Builder $query, array $filter): void
    {
        $query->when(data_get($filter, 'is_active'), function ($q, $active) {
            $q->where('is_active', (bool)$active);
        });
        
        $query->when(data_get($filter, 'group'), function ($q, $group) {
            $q->where('group', $group);
        });
    }
    
    protected function applySearch(Builder $query, array $filter): void
    {
        $search = data_get($filter, 'search');
        
        if (!$search) {
            return;
        }
        
        $query->whereHas('translations', function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }
}