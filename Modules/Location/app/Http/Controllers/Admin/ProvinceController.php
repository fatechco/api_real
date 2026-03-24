<?php
namespace Modules\Location\Http\Controllers\Admin;

use App\Helpers\ResponseError;
use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\FilterParamsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Location\Http\Requests\ProvinceRequest;
use Modules\Location\Http\Resources\ProvinceResource;
use Modules\Location\Models\Province;
use Modules\Location\Repositories\ProvinceRepository;
use Modules\Location\Services\ProvinceService;

class ProvinceController extends AdminBaseController
{

    public function __construct(
        private ProvinceRepository $repository,
        private ProvinceService $service
    )
    {
        parent::__construct();
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return ProvinceResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param Province $province
     * @return JsonResponse
     */
    public function show(Province $province): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ProvinceResource::make($this->repository->show($province))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ProvinceRequest $request
     * @return JsonResponse
     */
    public function store(ProvinceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ProvinceResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Update a newly created resource in storage.
     *
     */
    public function update(Province $province, ProvinceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($province, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ProvinceResource::make(data_get($result, 'data'))
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
            ProvinceResource::make(data_get($result, 'data'))
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
