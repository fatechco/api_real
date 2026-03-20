<?php
namespace Modules\Package\Http\Controllers\Frontend;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use Modules\Package\Http\Requests\CreditPurchaseRequest;
use Modules\Package\Http\Resources\CreditTransactionResource;
use Modules\Package\Http\Resources\UserPackageResource;
use Modules\Package\Repositories\UserPackageRepository;
use Modules\Package\Repositories\CreditTransactionRepository;
use Modules\Package\Services\PackageService;
use Modules\Package\Services\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionController extends Controller
{
    protected string $language;

    public function __construct(
        private UserPackageRepository $userPackageRepository,
        private CreditTransactionRepository $creditTransactionRepository,
        private PackageService $packageService,
        private CreditService $creditService
    )
    {
        parent::__construct();
        $this->language = request()->header('Accept-Language') ?? 'en';
        $this->middleware('auth:sanctum');
    }

    /**
     * Get current user's active subscription.
     *
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        $user = auth()->user();
        $userPackage = $this->userPackageRepository->getActiveUserPackage($user->id);

        if (!$userPackage) {
            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                [
                    'has_subscription' => false,
                    'listings_used' => $user->properties()
                        ->whereMonth('created_at', now()->month)
                        ->count(),
                    'listings_remaining' => 3 - $user->properties()
                        ->whereMonth('created_at', now()->month)
                        ->count()
                ]
            );
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'has_subscription' => true,
                'subscription' => UserPackageResource::make($userPackage->load('package')),
                'credits_balance' => $userPackage->getAvailableCredits()
            ]
        );
    }

    /**
     * Get user's subscription history.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function history(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $user = auth()->user();
        $models = $this->userPackageRepository->paginate($request->all(), $user->id);

        return UserPackageResource::collection($models);
    }

    /**
     * Purchase credits.
     *
     * @param CreditPurchaseRequest $request
     * @return JsonResponse
     */
    public function purchaseCredits(CreditPurchaseRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Calculate price (example: 10000 VND per credit)
        $pricePerCredit = 10000;
        $totalPrice = $validated['amount'] * $pricePerCredit;

        $result = $this->creditService->purchase(
            $user->id,
            $validated['amount'],
            $totalPrice,
            [
                'payment_method' => $validated['payment_method'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]
        );

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            CreditTransactionResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Get credit transaction history.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function creditTransactions(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $user = auth()->user();
        $models = $this->creditTransactionRepository->paginate($user->id, $request->all());

        return CreditTransactionResource::collection($models);
    }

    /**
     * Get credit balance.
     *
     * @return JsonResponse
     */
    public function creditBalance(): JsonResponse
    {
        $user = auth()->user();
        $balance = $this->creditService->getBalance($user->id);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'balance' => $balance,
                'formatted' => number_format($balance) . ' credits'
            ]
        );
    }
}