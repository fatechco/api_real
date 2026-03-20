<?php
namespace Modules\Package\Repositories;

use Modules\Package\Models\Package;

class PackageRepository
{
    public function __construct(
        protected Package $model
    ) {}

    public function paginate(array $filter)
    {
        return $this->model
            ->orderBy('sort_order')
            ->when(data_get($filter, 'type'), function ($q, $type) {
                $q->where('type', $type);
            })
            ->when(isset($filter['active']), function ($q) use ($filter) {
                $q->where('is_active', $filter['active']);
            })
            ->paginate(data_get($filter, 'perPage', 15));
    }

    public function show(int $id): ?Package
    {
        return $this->model->find($id);
    }

    public function getActivePackages(string $type = null)
    {
        $query = $this->model->where('is_active', true);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('sort_order')->get();
    }

    public function findByRole(string $roleName): ?Package
    {
        return $this->model->where('role_name', $roleName)->first();
    }
}