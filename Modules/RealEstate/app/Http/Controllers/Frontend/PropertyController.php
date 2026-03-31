<?php
namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Traits\ApiResponse;
use Modules\RealEstate\Http\Requests\PropertyRequest;
use Modules\RealEstate\Http\Requests\PropertySearchRequest;
use Modules\RealEstate\Http\Resources\PropertyResource;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Repositories\PropertyRepository;
use Modules\RealEstate\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\RealEstate\Http\Resources\PropertyDetailResource;
use Modules\RealEstate\Http\Resources\PropertyEditResource;
use Modules\RealEstate\Http\Resources\PropertyListResource;

class PropertyController extends Controller
{
    use ApiResponse;   
    public function __construct(
        protected PropertyRepository $repository,
        protected PropertyService $service
    ) {
        parent::__construct();
        $this->middleware('auth:sanctum')->except(['index', 'show', 'search']);
    }

    public function index(PropertySearchRequest $request): AnonymousResourceCollection
    {
        $properties = $this->repository->paginate($request->validated());
        return PropertyResource::collection($properties);
    }

    public function show(string $slug): JsonResponse
    {
        $property = $this->repository->findBySlug($slug);

        if (!$property) {
            return response()->json([
                'status' => false,
                'message' => __('property::property.errors.not_found')
            ], 404);
        }

        $this->repository->incrementViews(
            $property->id,
            auth()->check() ? auth()->id() : null
        );

        return response()->json([
            'status' => true,
            'message' => __('property::property.messages.success'),
            'data' => PropertyDetailResource::make($property->load(['user', 'images', 'amenities']))
        ]);
    }

    public function showByUuid(string $uuid): JsonResponse
    {
        $property = $this->repository->findByUuid($uuid);

        if (!$property) {
            return response()->json([
                'status' => false,
                'message' => __('property::property.errors.not_found')
            ], 404);
        }

        $this->repository->incrementViews(
            $property->id,
            auth()->check() ? auth()->id() : null
        );

        return response()->json([
            'status' => true,
            'message' => __('property::property.messages.success'),
            'data' => PropertyResource::make($property->load(['user', 'images', 'amenities']))
        ]);
    }

    public function search(PropertySearchRequest $request): AnonymousResourceCollection
    {
        $properties = $this->repository->paginate($request->validated());
        return PropertyResource::collection($properties);
    }

    
      /**
     * Get property for editing (authenticated)
     */
    public function edit(string $uuid): JsonResponse
    {
        $property = $this->repository->findByUuid($uuid);

        if (!$property) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_404,
                'message' => __('property::property.errors.not_found', locale: $this->language)
            ]);
        }

        // Check if user owns this property
        if ($property->user_id !== auth()->id()) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_403,
                'message' => __('property::property.errors.unauthorized', locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('property::property.messages.success', locale: $this->language),
            new PropertyEditResource($property->load(['translations', 'images', 'amenities']))
        );
    }

    public function store(PropertyRequest $request)
    {

        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return response()->json([
                'status' => false,
                'message' => data_get($result, 'message', 'Error')
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => __('property::property.messages.created'),
            'data' => PropertyResource::make(data_get($result, 'data'))
        ], 201);
    }

    public function update(Property $property, PropertyRequest $request): JsonResponse
    {
        if (!$property->canEdit(auth()->id())) {
            return response()->json([
                'status' => false,
                'message' => __('property::property.errors.unauthorized')
            ], 403);
        }

        $result = $this->service->update($property->id, $request->validated());

        if (!data_get($result, 'status')) {
            return response()->json([
                'status' => false,
                'message' => data_get($result, 'message', 'Error')
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => __('property::property.messages.updated'),
            'data' => PropertyResource::make(data_get($result, 'data'))
        ]);
    }

    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return response()->json([
                'status' => false,
                'message' => data_get($result, 'message', 'Error')
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => __('property::property.messages.deleted')
        ]);
    }

    public function myProperties(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $properties = $this->repository->getUserProperties(auth()->id(), $request->all());
        return PropertyListResource::collection($properties);
    }

    public function similar(Property $property): AnonymousResourceCollection
    {
        $similar = $this->repository->getSimilar($property);
        return PropertyListResource::collection($similar);
    }
}