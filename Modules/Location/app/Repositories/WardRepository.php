<?php

namespace Modules\Location\Repositories;

use Schema;

use Illuminate\Support\Facades\DB;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Location\Models\Ward;

class WardRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Ward::class;
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
            $column = Schema::hasColumn('wards', $column) ? $column : 'id';
        }

        return Ward::filter($filter)
            ->with([
                'translation' => fn($query) => $query->where('locale', $this->language),
            ])
            ->when(data_get($filter, 'district_id'), function ($query, $id) use ($sort) {
                $query->orderByRaw(DB::raw("FIELD(id, $id) $sort"));
            },
                fn($q) => $q->orderBy($column, $sort)
            )
            ->paginate($filter['perPage'] ?? 10);
    }

    public function show(Ward $model): Ward
    {
        return $model->load([
            'translation' => fn($query) => $query->where('locale', $this->language),
            'translations',
            'country.translation' => fn($query) => $query->where('locale', $this->language),
            'province.translation'    => fn($query) => $query->where('locale', $this->language),
            'district.translation'    => fn($query) => $query->where('locale', $this->language),
        ]);
    }

}
