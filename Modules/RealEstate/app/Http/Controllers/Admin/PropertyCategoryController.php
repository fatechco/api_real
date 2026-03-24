<?php
namespace Modules\RealEstate\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Models\PropertyCategory;
use Modules\RealEstate\Repositories\PropertyCategoryRepository;
use Modules\RealEstate\Http\Resources\PropertyCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropertyCategoryController extends Controller
{
    protected PropertyCategoryRepository $repository;
    
    public function __construct(PropertyCategoryRepository $repository)
    {
        $this->repository = $repository;
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|manager');
    }
    
    /**
     * Get list of categories with pagination
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
     * Create new category
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:property_categories,id',
            'icon' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,vi,fr,zh,ko',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create category
            $category = PropertyCategory::create([
                'slug' => Str::slug($validated['translations'][0]['name']),
                'parent_id' => $validated['parent_id'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'image' => $validated['image'] ?? null,
                'order' => $validated['order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);
            
            // Create translations
            foreach ($validated['translations'] as $translation) {
                $category->translations()->create([
                    'locale' => $translation['locale'],
                    'name' => $translation['name'],
                    'description' => $translation['description'] ?? null,
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => true,
                'message' => 'Category created successfully',
                'data' => new PropertyCategoryResource($category->load('translations')),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create category: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get category by ID
     */
    public function show(int $id): JsonResponse
    {
        $category = PropertyCategory::with(['translations', 'parent', 'children'])
            ->find($id);
        
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
     * Update category
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = PropertyCategory::find($id);
        
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }
        
        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:property_categories,id',
            'icon' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'translations' => 'nullable|array',
            'translations.*.locale' => 'required|string|in:en,vi,fr,zh,ko',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);
        
        // Prevent circular parent reference
        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            return response()->json([
                'status' => false,
                'message' => 'Category cannot be its own parent',
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update category
            $category->update([
                'parent_id' => $validated['parent_id'] ?? $category->parent_id,
                'icon' => $validated['icon'] ?? $category->icon,
                'image' => $validated['image'] ?? $category->image,
                'order' => $validated['order'] ?? $category->order,
                'is_active' => $validated['is_active'] ?? $category->is_active,
            ]);
            
            // Update slug if name changed
            if (isset($validated['translations'])) {
                $englishTranslation = collect($validated['translations'])->firstWhere('locale', 'en');
                if ($englishTranslation && $englishTranslation['name'] !== $category->getTranslation('name', 'en')) {
                    $category->slug = Str::slug($englishTranslation['name']);
                    $category->save();
                }
            }
            
            // Update translations
            if (isset($validated['translations'])) {
                foreach ($validated['translations'] as $translation) {
                    $category->translations()->updateOrCreate(
                        ['locale' => $translation['locale']],
                        [
                            'name' => $translation['name'],
                            'description' => $translation['description'] ?? null,
                        ]
                    );
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully',
                'data' => new PropertyCategoryResource($category->load('translations')),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update category: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete category
     */
    public function destroy(int $id): JsonResponse
    {
        $category = PropertyCategory::find($id);
        
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }
        
        try {
            // Check if category has children
            if ($category->children()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete category because it has ' . $category->children()->count() . ' subcategories',
                ], 400);
            }
            
            // Check if category has properties
            if ($category->properties()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete category because it is used by ' . $category->properties()->count() . ' properties',
                ], 400);
            }
            
            $category->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'Category deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Reorder categories
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:property_categories,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);
        
        try {
            foreach ($validated['orders'] as $item) {
                PropertyCategory::where('id', $item['id'])->update(['order' => $item['order']]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Categories reordered successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to reorder categories: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Toggle category active status
     */
    public function toggleActive(int $id): JsonResponse
    {
        $category = PropertyCategory::find($id);
        
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }
        
        $category->update(['is_active' => !$category->is_active]);
        
        return response()->json([
            'status' => true,
            'message' => $category->is_active ? 'Category activated' : 'Category deactivated',
            'data' => [
                'id' => $category->id,
                'is_active' => $category->is_active,
            ],
        ]);
    }
    
    /**
     * Get category statistics
     */
    public function statistics(): JsonResponse
    {
        $total = PropertyCategory::count();
        $active = PropertyCategory::where('is_active', true)->count();
        $inactive = $total - $active;
        $root = PropertyCategory::whereNull('parent_id')->count();
        $withChildren = PropertyCategory::has('children')->count();
        
        $mostUsed = PropertyCategory::withCount('properties')
            ->orderBy('properties_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'properties_count' => $category->properties_count,
                ];
            });
        
        return response()->json([
            'status' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'root_categories' => $root,
                'categories_with_children' => $withChildren,
                'most_used' => $mostUsed,
            ],
        ]);
    }
}