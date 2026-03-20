<?php
namespace Modules\Package\Repositories;

use Modules\Package\Models\UserPackage;

class UserPackageRepository
{
    public function __construct(
        protected UserPackage $model
    ) {}

    public function paginate(array $filter, int $userId = null)
    {
        $query = $this->model->with(['package', 'user']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if (data_get($filter, 'status')) {
            $query->where('status', $filter['status']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(data_get($filter, 'perPage', 15));
    }

    public function getActiveUserPackage(int $userId): ?UserPackage
    {
        return $this->model
            ->with('package')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function deactivateUserPackages(int $userId): void
    {
        $this->model
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);
    }

    public function incrementCredits(int $userPackageId, int $amount): UserPackage
    {
        $userPackage = $this->model->findOrFail($userPackageId);
        $userPackage->increment('credits_remaining', $amount);
        return $userPackage->fresh();
    }

    public function decrementCredits(int $userPackageId, int $amount): UserPackage
    {
        $userPackage = $this->model->findOrFail($userPackageId);
        $userPackage->decrement('credits_remaining', $amount);
        return $userPackage->fresh();
    }
}