<?php
namespace Modules\Location\Http\Controllers\Frontend;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Rest\RestBaseController;
use App\Http\Requests\FilterParamsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Location\Http\Resources\DistrictResource;
use Modules\Location\Models\District;
use Modules\Location\Repositories\DistrictRepository;
use Modules\Location\Http\Resources\WardResource;

class DistrictController extends RestBaseController
{
    public function __construct(
        private DistrictRepository $repository,
    )
    {
        parent::__construct();
    }

    /**
     * Get districts with pagination
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return DistrictResource::collection($model);
    }

    /**
     * Get all districts without pagination (for dropdowns)
     */
    public function all(FilterParamsRequest $request): JsonResponse
    {
        $districts = District::with(['translation', 'province', 'country'])
            ->filter($request->all())
            ->orderBy('order')
            ->get();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DistrictResource::collection($districts)
        );
    }

    /**
     * Get district by ID
     */
    public function show(District $district, FilterParamsRequest $request): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            new DistrictResource($district->load(['translations', 'province', 'country', 'wards']))
        );
    }

    /**
     * Get wards by district ID (with pagination)
     */
    public function wards(int $districtId, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $wards = $this->repository->getWards($districtId, $request->all());

        return WardResource::collection($wards);
    }

    /**
     * Get all wards by district ID (without pagination)
     */
    public function allWards(int $districtId, FilterParamsRequest $request): JsonResponse
    {
        $wards = $this->repository->getAllWards($districtId, $request->all());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            WardResource::collection($wards)
        );
    }
}