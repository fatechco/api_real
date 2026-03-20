<?php
namespace Modules\Package\Services;

use App\Helpers\ResponseError;
use Modules\Package\Models\Package;
use Modules\Package\Models\UserPackage;
use Modules\Package\Repositories\PackageRepository;
use Modules\Package\Repositories\UserPackageRepository;
use Modules\User\Models\User;
use DB;
use Log;

class PackageService
{
    public function __construct(
        protected PackageRepository $repository,
        protected UserPackageRepository $userPackageRepository,
        protected CreditService $creditService
    ) {}

    public function create(array $data): array
    {
        try {
            $package = Package::create($data);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $package
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => false,
                'code' => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    public function update(int $id, array $data): array
    {
        try {
            $package = Package::find($id);

            if (!$package) {
                return [
                    'status' => false,
                    'code' => ResponseError::ERROR_404,
                    'message' => 'Package not found'
                ];
            }

            $package->update($data);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $package
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => false,
                'code' => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    public function delete(array $ids): array
    {
        try {
            Package::whereIn('id', $ids)->delete();

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => false,
                'code' => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    public function changeActive(int $id): array
    {
        try {
            $package = Package::find($id);

            if (!$package) {
                return [
                    'status' => false,
                    'code' => ResponseError::ERROR_404,
                    'message' => 'Package not found'
                ];
            }

            $package->update([
                'is_active' => !$package->is_active
            ]);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $package
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => false,
                'code' => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    public function reorder(array $orders): array
    {
        try {
            DB::transaction(function () use ($orders) {
                foreach ($orders as $order) {
                    Package::where('id', $order['id'])
                        ->update(['sort_order' => $order['sort_order']]);
                }
            });

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => false,
                'code' => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    public function purchase(int $userId, int $packageId, array $paymentData = []): array
    {
        try {
            $package = Package::find($packageId);

            if (!$package || !$package->is_active) {
                return [
                    'status' => false,
                    'code' => ResponseError::ERROR_404,
                    'message' => 'Package not found or inactive'
                ];
            }

            // Deactivate current packages
            $this->userPackageRepository->deactivateUserPackages($userId);

            // Create new user package
            $userPackage = UserPackage::create([
                'user_id' => $userId,
                'package_id' => $packageId,
                'status' => 'pending',
                'credits_remaining' => $package->credits_per_month,
                'listings_used_this_month' => 0,
                'bonus_credits' => 0,
                'started_at' => null,
                'expires_at' => null
            ]);

            // TODO: Process payment here

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $userPackage
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => false,
                'code' => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    public function activate(int $userPackageId): array
    {
        try {
            $userPackage = UserPackage::find($userPackageId);

            if (!$userPackage) {
                return [
                    'status' => false,
                    'code' => ResponseError::ERROR_404,
                    'message' => 'User package not found'
                ];
            }

            $userPackage->update([
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => now()->addMonth()
            ]);

            // Assign role to user
            $user = User::find($userPackage->user_id);
            $user->assignRole($userPackage->package->role_name);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $userPackage->fresh('package')
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => false,
                'code' => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    public function cancel(int $userPackageId): array
    {
        try {
            $userPackage = UserPackage::find($userPackageId);

            if (!$userPackage) {
                return [
                    'status' => false,
                    'code' => ResponseError::ERROR_404,
                    'message' => 'User package not found'
                ];
            }

            $userPackage->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            // Revert to free role
            $user = User::find($userPackage->user_id);
            $freePackage = Package::where('role_name', 'member_basic')->first();
            if ($freePackage) {
                $user->syncRoles([$freePackage->role_name]);
            }

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $userPackage
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => false,
                'code' => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }
}