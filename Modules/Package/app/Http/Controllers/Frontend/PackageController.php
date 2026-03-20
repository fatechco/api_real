<?php
namespace Modules\Package\Http\Controllers\Frontend;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use Modules\Package\Http\Resources\PackageResource;
use Modules\Package\Repositories\PackageRepository;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    protected string $language;

    public function __construct(
        private PackageRepository $repository
    )
    {
        parent::__construct();
        $this->language = request()->header('Accept-Language') ?? 'en';
    }

    /**
     * Get all active packages.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function index(FilterParamsRequest $request): JsonResponse
    {
        $type = $request->get('type');
        $packages = $this->repository->getActivePackages($type);

        $grouped = [
            'member' => $packages->where('type', 'member')->values(),
            'agent' => $packages->where('type', 'agent')->values(),
            'agency' => $packages->where('type', 'agency')->values()
        ];

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $grouped
        );
    }

    /**
     * Get package details.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $package = $this->repository->show($id);

        if (!$package || !$package->is_active) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            PackageResource::make($package)
        );
    }

    /**
     * Get package by role name.
     *
     * @param string $roleName
     * @return JsonResponse
     */
    public function getByRole(string $roleName): JsonResponse
    {
        $package = $this->repository->findByRole($roleName);

        if (!$package || !$package->is_active) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            PackageResource::make($package)
        );
    }
}