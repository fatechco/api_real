<?php
namespace Modules\RealEstate\Http\Controllers\Admin;

use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\FilterParamsRequest;
use Modules\RealEstate\Http\Requests\PropertyRequest;
use Modules\RealEstate\Http\Resources\PropertyResource;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Repositories\PropertyRepository;
use Modules\RealEstate\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends AdminBaseController
{
    public function __construct(
        protected PropertyRepository $repository,
        protected PropertyService $service
    ) {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
       
        $properties = $this->repository->paginate($request->all());
        return PropertyResource::collection($properties);
    }

    public function show(int $id): JsonResponse
    {
        $property = $this->repository->show($id);

        if (!$property) {
            return response()->json([
                'status' => false,
                'message' => __('property::property.errors.not_found')
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => PropertyResource::make($property)
        ]);
    }

    public function update(Property $property, PropertyRequest $request): JsonResponse
    {
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

    public function approve(int $id): JsonResponse
    {
        $result = $this->service->approve($id);

        if (!data_get($result, 'status')) {
            return response()->json([
                'status' => false,
                'message' => data_get($result, 'message', 'Error')
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => __('property::property.messages.approved'),
            'data' => PropertyResource::make(data_get($result, 'data'))
        ]);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $result = $this->service->reject($id, $request->reason);

        if (!data_get($result, 'status')) {
            return response()->json([
                'status' => false,
                'message' => data_get($result, 'message', 'Error')
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => __('property::property.messages.rejected'),
            'data' => PropertyResource::make(data_get($result, 'data'))
        ]);
    }

    public function toggleFeature(int $id): JsonResponse
    {
        $result = $this->service->toggleFeature($id);

        if (!data_get($result, 'status')) {
            return response()->json([
                'status' => false,
                'message' => data_get($result, 'message', 'Error')
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => __('property::property.messages.feature_toggled'),
            'data' => PropertyResource::make(data_get($result, 'data'))
        ]);
    }
}