<?php
declare(strict_types=1);

namespace App\Repositories\AILogRepository;

use DB;
use App\Models\AILog;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AILogRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return AILog::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        return AILog::filter($filter)
            ->with([
                'user:id,firstname,lastname',
                'shop:id,uuid,slug',
                'shop.translation' => fn($query) => $query->where('locale', $this->language),
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return array
     */
    public function chart(array $filter): array
    {
        $type = match ($filter['type'] ?? 'day') {
            'year'  => '%Y',
            'month' => '%Y-%m',
            'week'  => '%Y-%m-%w',
            default => '%Y-%m-%d',
        };

        $chart = AILog::filter($filter)
            ->select([
                DB::raw('count(id) as count'),
                DB::raw('cast(sum(total_token) as unsigned) as total_token'),
                DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
            ])
            ->groupBy('time')
            ->get();

        $cards = AILog::filter($filter)
            ->select([
                DB::raw('count(id) as count'),
                DB::raw('cast(sum(total_token) as unsigned) as total_token'),
            ])
            ->first();

        return [
            'total_count' => $cards?->count ?? 0,
            'total_token' => $cards?->total_token ?? 0,
            'chart'       => $chart
        ];
    }

    /**
     * @param int $id
     * @return Builder|array|Collection|Model|null
     */
    public function show(int $id): Builder|array|Collection|Model|null
    {
        return AILog::with([
            'user:id,firstname,lastname',
            'shop:id,uuid,slug',
            'shop.translation' => fn($query) => $query->where('locale', $this->language),
        ])->find($id);
    }

}
