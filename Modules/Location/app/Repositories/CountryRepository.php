<?php
namespace Modules\Location\Repositories;

use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Location\Models\Country;
use Modules\Location\Models\Province;

class CountryRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Country::class;
    }

    public function paginate(array $filter): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';
        $sort = $filter['sort'] ?? 'desc';

        return Country::filter($filter)
            ->with(['translation'])
            ->orderBy($column, $sort)
            ->paginate($filter['perPage'] ?? 15);
    }

    public function show(Country $country, array $filter): Country
    {
        return $country->load([
            'translations',
            'provinces' => function($q) use ($filter) {
                $q->with(['translation'])
                  ->when(data_get($filter, 'search'), function($query, $search) {
                      $query->whereHas('translations', function($t) use ($search) {
                          $t->where('name', 'like', "%{$search}%");
                      });
                  })
                  ->orderBy('order');
            }
        ]);
    }

    /**
     * Get provinces by country ID with pagination
     */
    public function getProvinces(int $countryId, array $filter): LengthAwarePaginator
    {
        return Province::with(['translation'])
            ->where('country_id', $countryId)
            ->filter($filter)
            ->orderBy('order')
            ->paginate($filter['perPage'] ?? 15);
    }

    /**
     * Get all provinces by country ID without pagination
     */
    public function getAllProvinces(int $countryId, array $filter): \Illuminate\Support\Collection
    {
        return Province::with(['translation'])
            ->where('country_id', $countryId)
            ->filter($filter)
            ->orderBy('order')
            ->get();
    }

    public function checkCountry(int $id, array $filter): ?Country
    {
        $province = data_get($filter, 'province');

        return Country::with([
            'provinces.translation' => fn($q) => $q->where('name', 'like', "%$province%")
        ])
            ->whereHas('provinces.translation', function ($query) use ($province) {
                $query->where('name', 'like', "%$province%");
            })
            ->find($id);
    }
}