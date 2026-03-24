<?php
// Modules/RealEstate/Http/Controllers/Frontend/AmenityController.php

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Repositories\AmenityRepository;
use Modules\RealEstate\Http\Resources\AmenityResource;
use Modules\RealEstate\Http\Resources\PropertyResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AmenityController extends Controller
{
    protected AmenityRepository $repository;
    
    public function __construct(AmenityRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Get list of amenities
     */
    public function index(Request $request): JsonResponse
    {
        $amenities = $this->repository->paginate($request->all());
        
        return response()->json([
            'status' => true,
            'data' => AmenityResource::collection($amenities),
            'meta' => [
                'current_page' => $amenities->currentPage(),
                'last_page' => $amenities->lastPage(),
                'per_page' => $amenities->perPage(),
                'total' => $amenities->total(),
            ],
        ]);
    }
    
    /**
     * Get popular amenities
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 12);
        $amenities = $this->repository->getPopular($limit);
        
        return response()->json([
            'status' => true,
            'data' => AmenityResource::collection($amenities),
        ]);
    }
    
    /**
     * Get grouped amenities
     */
    public function grouped(Request $request): JsonResponse
    {
        $groups = $this->repository->getGrouped();
        
        return response()->json([
            'status' => true,
            'data' => $groups,
        ]);
    }
    
    /**
     * Search amenities
     */
    public function search(Request $request): JsonResponse
    {
        $keyword = $request->get('keyword');
        $amenities = $this->repository->search($keyword, $request->all());
        
        return response()->json([
            'status' => true,
            'data' => AmenityResource::collection($amenities),
            'meta' => [
                'keyword' => $keyword,
                'total' => $amenities->total(),
            ],
        ]);
    }
    
    /**
     * Get amenity by ID
     */
    public function show(int $id): JsonResponse
    {
        $amenity = $this->repository->find($id);
        
        if (!$amenity) {
            return response()->json([
                'status' => false,
                'message' => 'Amenity not found',
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'data' => new AmenityResource($amenity),
        ]);
    }
    
    /**
     * Get properties by amenity
     */
    public function properties(string $slug, Request $request): JsonResponse
    {
        $amenity = $this->repository->findBySlug($slug);
        
        if (!$amenity) {
            return response()->json([
                'status' => false,
                'message' => 'Amenity not found',
            ], 404);
        }
        
        $properties = $this->repository->getProperties($amenity->id, $request->all());
        
        return response()->json([
            'status' => true,
            'data' => PropertyResource::collection($properties),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
        ]);
    }
}