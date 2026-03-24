<?php
namespace Modules\Location\Repositories;

use App\Repositories\CoreRepository;
use Modules\Location\Models\Province;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Location\Models\District;

class ProvinceRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Province::class;
    }

    /**
     * Get paginated list of provinces
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filter);
        $this->applySearch($query, $filter);
        $this->withTranslation($query);
        
        // Load country translation
        $query->with(['country' => function ($q) {
            $this->withTranslation($q);
        }]);
        
        $this->applySorting($query, $filter);
        
        return $query->paginate($this->getPerPage($filter));
    }

    /**
     * Get provinces by country
     */
    public function getByCountry(int $countryId, array $filter = []): LengthAwarePaginator
    {
        $query = $this->model->where('country_id', $countryId);
        
        $this->applyFilters($query, $filter);
        $this->applySearch($query, $filter);
        $this->withTranslation($query);
        $this->applySorting($query, $filter);
        
        return $query->paginate($this->getPerPage($filter));
    }

    /**
     * Get province with districts
     */
    public function show(Province $province): Province
    {
        return $province->load([
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language),
            'country.translation' => fn($q) => $q->where('locale', $this->language),
            'districts' => fn($q) => $q->with([
                'translation' => fn($t) => $t->where('locale', $this->language),
                'wards' => fn($w) => $w->with([
                    'translation' => fn($t) => $t->where('locale', $this->language),
                ]),
            ]),
        ]);
    }

    protected function applyFilters(Builder $query, array $filter): void
    {
        $query->when(data_get($filter, 'country_id'), function ($q, $id) {
            $q->where('country_id', $id);
        });
        
        $query->when(data_get($filter, 'code'), function ($q, $code) {
            $q->where('code', $code);
        });
        
        $query->when(data_get($filter, 'type'), function ($q, $type) {
            $q->where('type', $type);
        });
        
        $query->when(isset($filter['active']), function ($q) use ($filter) {
            $q->where('active', (bool)$filter['active']);
        });
        
        $query->when(data_get($filter, 'ids'), function ($q, $ids) {
            $ids = is_array($ids) ? $ids : explode(',', $ids);
            $q->whereIn('id', $ids);
        });
    }

    protected function applySearch(Builder $query, array $filter): void
    {
        $search = data_get($filter, 'search');
        
        if (empty($search)) {
            return;
        }
        
        $query->where(function ($q) use ($search) {
            $q->where('code', 'LIKE', "%{$search}%")
              ->orWhereHas('translations', function ($t) use ($search) {
                  $t->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

    protected function applySorting(Builder $query, array $filter): void
    {
        $column = $this->getOrderColumn($filter);
        $direction = $this->getOrderDirection($filter);
        
        $query->orderBy($column, $direction);
    }

      /**
     * Get districts by province ID with pagination
     */
    public function getDistricts(int $provinceId, array $filter): LengthAwarePaginator
    {
        return District::with(['translation'])
            ->where('province_id', $provinceId)
            ->filter($filter)
            ->orderBy('order')
            ->paginate($filter['perPage'] ?? 15);
    }

    /**
     * Get all districts by province ID without pagination
     */
    public function getAllDistricts(int $provinceId, array $filter): \Illuminate\Support\Collection
    {
        return District::with(['translation'])
            ->where('province_id', $provinceId)
            ->filter($filter)
            ->orderBy('order')
            ->get();
    }

}