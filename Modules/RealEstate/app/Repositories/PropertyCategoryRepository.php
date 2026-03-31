<?php
namespace Modules\RealEstate\Repositories;

use App\Repositories\CoreRepository;
use Modules\RealEstate\Models\PropertyCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        
        $perPage = $filter['per_page'] ?? 15;
        
        return $query->orderBy('order')
            ->paginate($perPage);
    }
    
    public function getTree(): Collection
    {
        return $this->model()
            ->with(['children' => function($query) {
                $query->with(['children', 'translations']);
            }, 'translations'])
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
    
    public function getWithPropertiesCount(): Collection
    {
        return $this->model()
            ->with(['translations'])
            ->withCount('properties')
            ->orderBy('order')
            ->get();
    }
    
    public function findById(int $id): ?PropertyCategory
    {
        return $this->model()
            ->with(['translations', 'parent', 'children' => function($query) {
                $query->with(['translations', 'children']);
            }])
            ->find($id);
    }
    
    public function findBySlug(string $slug): ?PropertyCategory
    {
        return $this->model()
            ->with(['translations', 'children.translations'])
            ->where('slug', $slug)
            ->first();
    }
    
    public function getStatistics(): array
    {
        $total = PropertyCategory::count();
        $active = PropertyCategory::where('is_active', true)->count();
        $inactive = $total - $active;
        $root = PropertyCategory::whereNull('parent_id')->count();
        $withChildren = PropertyCategory::has('children')->count();
        
        $mostUsed = PropertyCategory::withCount('properties')
            ->orderBy('properties_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'properties_count' => $category->properties_count,
                ];
            });
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'root_categories' => $root,
            'categories_with_children' => $withChildren,
            'most_used' => $mostUsed,
        ];
    }
    
    public function updateOrder(array $orders): bool
    {
        try {
            DB::beginTransaction();
            
            foreach ($orders as $item) {
                $this->model()
                    ->where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
    
    protected function applyFilters(Builder $query, array $filter): void
    {
        if (isset($filter['is_active'])) {
            $query->where('is_active', (bool) $filter['is_active']);
        }
        
        if (isset($filter['parent_id'])) {
            $query->where('parent_id', $filter['parent_id']);
        }
        
        if (isset($filter['search'])) {
            $this->applySearch($query, $filter);
        }
    }
    
    protected function applySearch(Builder $query, array $filter): void
    {
        $search = $filter['search'] ?? null;
        
        if (!$search) {
            return;
        }
        
        $query->where(function($q) use ($search) {
            $q->whereHas('translations', function($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%");
            })->orWhere('slug', 'like', "%{$search}%");
        });
    }
}