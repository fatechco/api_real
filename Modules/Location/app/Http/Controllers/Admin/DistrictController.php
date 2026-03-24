<?php

namespace Modules\Location\Http\Controllers\Admin;

use App\Helpers\ResponseError;
use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\FilterParamsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Location\Http\Requests\DistrictRequest;
use Modules\Location\Http\Resources\DistrictResource;
use Modules\Location\Models\District;
use Modules\Location\Repositories\DistrictRepository;
use Modules\Location\Services\DistrictService;

class DistrictController extends AdminBaseController
{

    public function __construct(
        private DistrictRepository $repository,
        private DistrictService $service
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return DistrictResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param District $district
     * @return JsonResponse
     */
    public function show(District $district): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DistrictResource::make($this->repository->show($district))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DistrictRequest $request
     * @return JsonResponse
     */
    public function store(DistrictRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DistrictResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Update a newly created resource in storage.
     *
     */
    public function update(District $district, DistrictRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($district, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DistrictResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Update a newly created resource in storage.
     *
     */
    public function changeActive(int $id): JsonResponse
    {
        $result = $this->service->changeActive($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DistrictResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
