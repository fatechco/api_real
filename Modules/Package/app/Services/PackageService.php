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
        protected CreditService $creditService,
        protected StorageService $storageService
    ) {}

         /**
     * Check if user can create a new listing
     */
    public function canCreateListing(int $userId): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [
                'can' => false,
                'reason' => 'User not found'
            ];
        }

        // Get user's active package
        $userPackage = UserPackage::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        // If no active package, assign free package
        if (!$userPackage) {
            $this->assignFreePackage($userId);
            
            // Get newly assigned package
            $userPackage = UserPackage::where('user_id', $userId)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();
        }

        if (!$userPackage) {
            return [
                'can' => false,
                'reason' => 'No package available. Please contact administrator.'
            ];
        }

        $package = $userPackage->package;
        $limits = is_string($package->limits) ? json_decode($package->limits, true) : $package->limits ?? [];

        // Check listing limit
        $listingsLimit = $limits['listingsPerMonth'] ?? 0;
        $listingsUsed = $userPackage->listings_used_this_month ?? 0;

        if ($listingsLimit > 0 && $listingsUsed >= $listingsLimit) {
            return [
                'can' => false,
                'reason' => "You have reached your monthly listing limit of {$listingsLimit}. Please upgrade your package."
            ];
        }

        return [
            'can' => true,
            'data' => [
                'remaining' => max(0, $listingsLimit - $listingsUsed),
                'limit' => $listingsLimit,
                'used' => $listingsUsed,
                'package' => $package
            ]
        ];
    }

    /**
     * Assign free package to user
     */
    public function assignFreePackage(int $userId): ?UserPackage
    {
        try {
            DB::beginTransaction();
            
            // Get free package
            $freePackage = Package::where('role_name', 'member_basic')
                ->where('is_active', true)
                ->first();

            if (!$freePackage) {
                \Log::error('Free package not found. Please run PackageSeeder first.');
                return null;
            }

            // Check if user already has an active package
            $existingPackage = UserPackage::where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if ($existingPackage) {
                // Ensure storage usage exists
                $this->storageService->ensureStorageUsageExists($userId, $existingPackage->id);
                DB::commit();
                return $existingPackage;
            }

            // Check if user has an expired package (for reactivation)
            $expiredPackage = UserPackage::where('user_id', $userId)
                ->where('status', 'expired')
                ->first();

            $userPackage = null;

            if ($expiredPackage) {
                $expiredPackage->update([
                    'package_id' => $freePackage->id,
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'credits_remaining' => $freePackage->credits_per_month,
                    'listings_used_this_month' => 0,
                ]);
                $userPackage = $expiredPackage;
            } else {
                // Create new user package
                $userPackage = UserPackage::create([
                    'user_id' => $userId,
                    'package_id' => $freePackage->id,
                    'status' => 'active',
                    'credits_remaining' => $freePackage->credits_per_month,
                    'listings_used_this_month' => 0,
                    'bonus_credits' => 0,
                    'storage_used_bytes' => 0,
                    'started_at' => now(),
                    'expires_at' => now()->addMonth(),
                ]);
            }

            // Create storage usage record
            $this->storageService->ensureStorageUsageExists($userId, $userPackage->id);
            
            // Assign role to user
            $user = User::find($userId);
            if ($user && !$user->hasRole($freePackage->role_name)) {
                $user->assignRole($freePackage->role_name);
            }
            
            DB::commit();

            return $userPackage;
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to assign free package: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Get or create user package
     */
    public function getOrCreateUserPackage(int $userId): ?UserPackage
    {
        $userPackage = UserPackage::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if (!$userPackage) {
            $userPackage = $this->assignFreePackage($userId);
        }

        return $userPackage;
    }

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