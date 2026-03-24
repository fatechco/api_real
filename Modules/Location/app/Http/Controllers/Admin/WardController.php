<?php

namespace Modules\Location\Http\Controllers\Admin;

use App\Helpers\ResponseError;
use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\FilterParamsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Location\Http\Requests\WardRequest;
use Modules\Location\Http\Resources\WardResource;
use Modules\Location\Models\Ward;
use Modules\Location\Repositories\WardRepository;
use Modules\Location\Services\WardService;

class WardController extends AdminBaseController
/*************  ✨ Windsurf Command ⭐  *************/
    /**
     * RegionController constructor.
     *
     * @param RegionRepository $repository
     * @param RegionService $service
     */
/*******  a83e354d-fbb1-4960-9b85-1534e0dbfc6c  *******/{

    public function __construct(
        private WardRepository $repository,
        private WardService $service
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return WardResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param Ward $ward
     * @return JsonResponse
     */
    public function show(Ward $ward): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            WardResource::make($this->repository->show($ward))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param WardRequest $request
     * @return JsonResponse
     */
    public function store(WardRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            WardResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Update a newly created resource in storage.
     *
     */
    public function update(Ward $ward, WardRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($ward, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            WardResource::make(data_get($result, 'data'))
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
            WardResource::make(data_get($result, 'data'))
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
