<?php
namespace Modules\Package\Services;

use App\Helpers\ResponseError;
use Modules\Package\Models\UserPackage;
use Modules\Package\Repositories\CreditTransactionRepository;
use Modules\Package\Repositories\UserPackageRepository;
use Log;

class CreditService
{
    public function __construct(
        protected CreditTransactionRepository $repository,
        protected UserPackageRepository $userPackageRepository
    ) {}

    public function getBalance(int $userId): int
    {
        $userPackage = $this->userPackageRepository->getActiveUserPackage($userId);
        
        if (!$userPackage) {
            return 0;
        }

        return $userPackage->getAvailableCredits();
    }

    public function purchase(int $userId, int $amount, float $price, array $metadata = []): array
    {
        try {
            $userPackage = $this->userPackageRepository->getActiveUserPackage($userId);

            if (!$userPackage) {
                return [
                    'status' => false,
                    'code' => ResponseError::ERROR_400,
                    'message' => 'No active package found'
                ];
            }

            // Add bonus credits
            $userPackage->increment('bonus_credits', $amount);

            // Create transaction
            $transaction = $this->repository->create([
                'user_id' => $userId,
                'user_package_id' => $userPackage->id,
                'type' => 'purchase',
                'amount' => $amount,
                'balance_after' => $userPackage->getAvailableCredits(),
                'description' => "Purchased {$amount} credits",
                'metadata' => array_merge($metadata, ['price' => $price])
            ]);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $transaction
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

    public function use(int $userId, int $userPackageId, int $amount, string $referenceType, int $referenceId, array $metadata = []): array
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

            if ($userPackage->getAvailableCredits() < $amount) {
                return [
                    'status' => false,
                    'code' => ResponseError::ERROR_400,
                    'message' => 'Insufficient credits'
                ];
            }

            // Deduct credits (priority: package credits first)
            $remaining = $amount;

            if ($userPackage->credits_remaining > 0) {
                $fromPackage = min($userPackage->credits_remaining, $remaining);
                $userPackage->decrement('credits_remaining', $fromPackage);
                $remaining -= $fromPackage;
            }

            if ($remaining > 0 && $userPackage->bonus_credits > 0) {
                $fromBonus = min($userPackage->bonus_credits, $remaining);
                $userPackage->decrement('bonus_credits', $fromBonus);
                $remaining -= $fromBonus;
            }

            // Create transaction
            $transaction = $this->repository->create([
                'user_id' => $userId,
                'user_package_id' => $userPackageId,
                'type' => 'usage',
                'amount' => -$amount,
                'balance_after' => $userPackage->getAvailableCredits(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => "Used {$amount} credits",
                'metadata' => $metadata
            ]);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $transaction
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