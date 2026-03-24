<?php
namespace Modules\RealEstate\Repositories;

use App\Repositories\CoreRepository;
use Modules\RealEstate\Models\PropertyCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PropertyCategoryRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return PropertyCategory::class;
    }
    
    public function paginate(array $filter): LengthAwarePaginator
    {
        $query = $this->model()->with(['translations']);
        
        $this->applyFilters($query, $filter);
        $this->applySearch($query, $filter);
        
        return $query->orderBy('order')
            ->paginate($filter['per_page'] ?? 15);
    }
    
    public function getTree(): Collection
    {
        return $this->model()
            ->with(['children.translations', 'translations'])
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();
    }
    
    public function getRootCategories(): Collection
    {
        return $this->model()
            ->with(['translations'])
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();
    }
    
    public function findBySlug(string $slug): ?PropertyCategory
    {
        return $this->model()
            ->with(['translations', 'children.translations'])
            ->where('slug', $slug)
            ->first();
    }
    
    public function getProperties(int $categoryId, array $filter = [])
    {
        $category = $this->model()->find($categoryId);
        
        if (!$category) {
            return [];
        }
        
        $query = $category->properties()
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
        
        $query->when(data_get($filter, 'parent_id'), function ($q, $parentId) {
            $q->where('parent_id', $parentId);
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
        })->orWhere('slug', 'like', "%{$search}%");
    }
}