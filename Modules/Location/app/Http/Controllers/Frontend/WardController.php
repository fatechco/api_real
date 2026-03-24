<?php
namespace Modules\Location\Http\Controllers\Frontend;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Rest\RestBaseController;
use App\Http\Requests\FilterParamsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Location\Http\Resources\WardResource;
use Modules\Location\Models\Ward;
use Modules\Location\Repositories\WardRepository;

class WardController extends RestBaseController
{
    public function __construct(
        private WardRepository $repository,
    )
    {
        parent::__construct();
    }

    /**
     * Get wards with pagination
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return WardResource::collection($model);
    }

    /**
     * Get all wards without pagination (for dropdowns)
     */
    public function all(FilterParamsRequest $request): JsonResponse
    {
        $wards = Ward::with(['translation', 'district', 'province', 'country'])
            ->filter($request->all())
            ->orderBy('order')
            ->get();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            WardResource::collection($wards)
        );
    }

    /**
     * Get ward by ID
     */
    public function show(Ward $ward, FilterParamsRequest $request): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            new WardResource($ward->load(['translations', 'district', 'province', 'country']))
        );
    }
}