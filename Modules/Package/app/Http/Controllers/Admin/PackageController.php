<?php
namespace Modules\Package\Http\Controllers\Admin;

use App\Helpers\ResponseError;
use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\FilterParamsRequest;
use Modules\Package\Http\Requests\PackageRequest;
use Modules\Package\Http\Resources\PackageResource;
use Modules\Package\Models\Package;
use Modules\Package\Repositories\PackageRepository;
use Modules\Package\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PackageController extends AdminBaseController
{
    public function __construct(
        private PackageRepository $repository,
        private PackageService $service
    )
    {
        parent::__construct();
    }

    /**
     * Display a listing of packages.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->all());

        return PackageResource::collection($models);
    }

    /**
     * Display the specified package.
     *
     * @param Package $package
     * @return JsonResponse
     */
    public function show(Package $package): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            PackageResource::make($package)
        );
    }

    /**
     * Store a newly created package.
     *
     * @param PackageRequest $request
     * @return JsonResponse
     */
    public function store(PackageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            PackageResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Update the specified package.
     *
     * @param Package $package
     * @param PackageRequest $request
     * @return JsonResponse
     */
    public function update(Package $package, PackageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($package->id, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            PackageResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Toggle package active status.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function changeActive(int $id): JsonResponse
    {
        $result = $this->service->changeActive($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            PackageResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified packages.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * Reorder packages.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function reorder(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->reorder($request->input('orders', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            []
        );
    }

    /**
     * Drop all packages (for testing).
     *
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        Package::truncate();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}