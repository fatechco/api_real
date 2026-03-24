<?php

namespace Modules\Location\Repositories;

use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Location\Models\District;
use Modules\Location\Models\Ward;
use Schema;

class DistrictRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return District::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        

        $column = $filter['column'] ?? 'id';
        $sort   = $filter['sort'] ?? 'desc';

        if ($column !== 'id') {
            $column = Schema::hasColumn('areas', $column) ? $column : 'id';
        }

        return District::filter($filter)
            ->with([
                'translation' => fn($query) => $query->where('locale', $this->language),
            ])
            ->when(data_get($filter, 'area_id'), function ($query, $id) use ($sort) {
                $query->orderByRaw(DB::raw("FIELD(id, $id) $sort"));
            },
                fn($q) => $q->orderBy($column, $sort)
            )
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param District $model
     * @return District
     */
    public function show(District $model): District
    {
        

        return $model->load([
            'country.translation' => fn($query) => $query->where('locale', $this->language),
            'province.translation'    => fn($query) => $query->where('locale', $this->language),
            'translation'         => fn($query) => $query->where('locale', $this->language),
            'translations',
        ]);
    }

    /**
     * Get wards by district ID with pagination
     */
    public function getWards(int $districtId, array $filter): LengthAwarePaginator
    {
        return Ward::with(['translation'])
            ->where('district_id', $districtId)
            ->filter($filter)
            ->orderBy('order')
            ->paginate($filter['perPage'] ?? 15);
    }

    /**
     * Get all wards by district ID without pagination
     */
    public function getAllWards(int $districtId, array $filter): \Illuminate\Support\Collection
    {
        return Ward::with(['translation'])
            ->where('district_id', $districtId)
            ->filter($filter)
            ->orderBy('order')
            ->get();
    }

}
