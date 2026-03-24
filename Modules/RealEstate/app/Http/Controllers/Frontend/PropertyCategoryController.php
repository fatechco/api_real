<?php

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Repositories\PropertyCategoryRepository;
use Modules\RealEstate\Http\Resources\PropertyCategoryResource;
use Modules\RealEstate\Http\Resources\PropertyResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PropertyCategoryController extends Controller
{
    protected PropertyCategoryRepository $repository;
    
    public function __construct(PropertyCategoryRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Get list of categories
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->repository->paginate($request->all());
        
        return response()->json([
            'status' => true,
            'data' => PropertyCategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }
    
    /**
     * Get category tree
     */
    public function tree(): JsonResponse
    {
        $categories = $this->repository->getTree();
        
        return response()->json([
            'status' => true,
            'data' => PropertyCategoryResource::collection($categories),
        ]);
    }
    
    /**
     * Get root categories
     */
    public function root(): JsonResponse
    {
        $categories = $this->repository->getRootCategories();
        
        return response()->json([
            'status' => true,
            'data' => PropertyCategoryResource::collection($categories),
        ]);
    }
    
    /**
     * Get category by slug
     */
    public function show(string $slug): JsonResponse
    {
        $category = $this->repository->findBySlug($slug);
        
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'data' => new PropertyCategoryResource($category),
        ]);
    }
    
    /**
     * Get properties by category
     */
    public function properties(string $slug, Request $request): JsonResponse
    {
        $category = $this->repository->findBySlug($slug);
        
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }
        
        $properties = $this->repository->getProperties($category->id, $request->all());
        
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