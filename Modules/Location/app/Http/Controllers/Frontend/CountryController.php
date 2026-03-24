<?php
namespace Modules\Location\Http\Controllers\Frontend;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Rest\RestBaseController;
use App\Http\Requests\FilterParamsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Location\Http\Resources\CountryResource;
use Modules\Location\Models\Country;
use Modules\Location\Repositories\CountryRepository;
use Modules\Location\Http\Resources\ProvinceResource;

class CountryController extends RestBaseController
{
    public function __construct(
        private CountryRepository $repository,
    )
    {
        parent::__construct();
    }

    /**
     * Get countries with pagination
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return CountryResource::collection($model);
    }

    /**
     * Get all countries without pagination (for dropdowns)
     */
    public function all(FilterParamsRequest $request): JsonResponse
    {
        $countries = Country::with(['translation'])
            ->where('active', true)
            ->filter($request->all())
            ->orderBy('order')
            ->get();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CountryResource::collection($countries)
        );
    }

    /**
     * Get country by ID
     */
    public function show(Country $country, FilterParamsRequest $request): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CountryResource::make($this->repository->show($country, $request->all()))
        );
    }

    /**
     * Get provinces by country ID (with pagination)
     */
    public function provinces(int $countryId, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $provinces = $this->repository->getProvinces($countryId, $request->all());

        return ProvinceResource::collection($provinces);
    }

    /**
     * Get all provinces by country ID (without pagination)
     */
    public function allProvinces(int $countryId, FilterParamsRequest $request): JsonResponse
    {
        $provinces = $this->repository->getAllProvinces($countryId, $request->all());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ProvinceResource::collection($provinces)
        );
    }

    /**
     * Search countries
     */
    public function search(FilterParamsRequest $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $countries = Country::with(['translation'])
            ->where(function($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                  ->orWhereHas('translations', function($t) use ($keyword) {
                      $t->where('name', 'like', "%{$keyword}%")
                        ->orWhere('native_name', 'like', "%{$keyword}%");
                  });
            })
            ->limit(20)
            ->get();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CountryResource::collection($countries)
        );
    }

    /**
     * Check country
     */
    public function checkCountry(int $id, FilterParamsRequest $request): JsonResponse
    {
        $result = $this->repository->checkCountry($id, $request->all());

        if (empty($result)) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_400,
                'message' => __('errors.' . ResponseError::ERROR_400, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CountryResource::make($result)
        );
    }
}