<?php
namespace Modules\Location\Http\Controllers\Frontend;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Rest\RestBaseController;
use App\Http\Requests\FilterParamsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Location\Http\Resources\ProvinceResource;
use Modules\Location\Models\Province;
use Modules\Location\Repositories\ProvinceRepository;
use Modules\Location\Http\Resources\DistrictResource;

class ProvinceController extends RestBaseController
{
    public function __construct(
        private ProvinceRepository $repository,
    )
    {
        parent::__construct();
    }

    /**
     * Get provinces with pagination
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return ProvinceResource::collection($model);
    }

    /**
     * Get all provinces without pagination (for dropdowns)
     */
    public function all(FilterParamsRequest $request): JsonResponse
    {
        $provinces = Province::with(['translation', 'country'])
            ->filter($request->all())
            ->orderBy('order')
            ->get();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ProvinceResource::collection($provinces)
        );
    }

    /**
     * Get province by ID
     */
    public function show(Province $province, FilterParamsRequest $request): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            new ProvinceResource($province->load(['translations', 'country', 'districts']))
        );
    }

    /**
     * Get districts by province ID (with pagination)
     */
    public function districts(int $provinceId, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $districts = $this->repository->getDistricts($provinceId, $request->all());

        return DistrictResource::collection($districts);
    }

    /**
     * Get all districts by province ID (without pagination)
     */
    public function allDistricts(int $provinceId, FilterParamsRequest $request): JsonResponse
    {
        $districts = $this->repository->getAllDistricts($provinceId, $request->all());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DistrictResource::collection($districts)
        );
    }
}