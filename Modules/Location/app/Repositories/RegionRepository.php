<?php

namespace Modules\Location\Repositories;

use Schema;

use Illuminate\Support\Facades\DB;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Location\Models\Region;

class RegionRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Region::class;
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
            $column = Schema::hasColumn('regions', $column) ? $column : 'id';
        }

        return Region::filter($filter)
            ->with([
                'translation' => fn($query) => $query->where('locale', $this->language),
            ])
            ->when(data_get($filter, 'region_id'), function ($query, $id) use ($sort) {
                $query->orderByRaw(DB::raw("FIELD(id, $id) $sort"));
            },
                fn($q) => $q->orderBy($column, $sort)
            )
            ->paginate($filter['perPage'] ?? 10);
    }

    public function show(Region $model): Region
    {
        return $model->load([
            'translation' => fn($query) => $query->where('locale', $this->language),
            'translations',
        ]);
    }

}
