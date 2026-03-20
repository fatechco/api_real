<?php
namespace Modules\Package\Repositories;

use Modules\Package\Models\CreditTransaction;

class CreditTransactionRepository
{
    public function __construct(
        protected CreditTransaction $model
    ) {}

    public function paginate(int $userId, array $filter)
    {
        $query = $this->model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (data_get($filter, 'type')) {
            $query->where('type', $filter['type']);
        }

        if (data_get($filter, 'from_date')) {
            $query->whereDate('created_at', '>=', $filter['from_date']);
        }

        if (data_get($filter, 'to_date')) {
            $query->whereDate('created_at', '<=', $filter['to_date']);
        }

        return $query->paginate(data_get($filter, 'perPage', 15));
    }

    public function create(array $data): CreditTransaction
    {
        return $this->model->create($data);
    }
}