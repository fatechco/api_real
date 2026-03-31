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
            'data' => PropertyCategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,vi,fr,zh,ko',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('categories', 'public');
            }
            
            // Create category
            $category = PropertyCategory::create([
                'slug' => Str::slug($validated['translations'][0]['name']),
                'parent_id' => $validated['parent_id'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'image' => $imagePath,
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
                'data' => new PropertyCategoryResource($category->load('translations')),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create category: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get category by ID
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->repository->findById($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }
        
        return response()->json([
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
                'message' => 'Category not found',
            ], 404);
        }
        
        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:property_categories,id',
            'icon' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'translations' => 'nullable|array',
            'translations.*.locale' => 'required|string|in:en,vi,fr,zh,ko',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);
        
        // Prevent circular parent reference
        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            return response()->json([
                'message' => 'Category cannot be its own parent',
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('categories', 'public');
                $validated['image'] = $imagePath;
            }
            
            // Update category
            $category->update([
                'parent_id' => $validated['parent_id'] ?? $category->parent_id,
                'icon' => $validated['icon'] ?? $category->icon,
                'image' => $validated['image'] ?? $category->image,
                'order' => $validated['order'] ?? $category->order,
                'is_active' => $validated['is_active'] ?? $category->is_active,
            ]);
            
            // Update slug if English name changed
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
                'data' => new PropertyCategoryResource($category->load('translations')),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update category: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete category
     */
    public function destroy(int $id): JsonResponse
    {
        $category = PropertyCategory::with(['children', 'properties'])->find($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }
        
        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category because it has ' . $category->children()->count() . ' subcategories',
            ], 400);
        }
        
        // Check if category has properties
        if ($category->properties()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category because it is used by ' . $category->properties()->count() . ' properties',
            ], 400);
        }
        
        try {
            $category->delete();
            
            return response()->json([
                'message' => 'Category deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
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
        
        $success = $this->repository->updateOrder($validated['orders']);
        
        if (!$success) {
            return response()->json([
                'message' => 'Failed to reorder categories',
            ], 500);
        }
        
        return response()->json([
            'message' => 'Categories reordered successfully',
        ]);
    }
    
    /**
     * Toggle category active status
     */
    public function toggleActive(int $id): JsonResponse
    {
        $category = PropertyCategory::find($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }
        
        $category->update(['is_active' => !$category->is_active]);
        
        return response()->json([
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
        $stats = $this->repository->getStatistics();
        
        return response()->json([
            'data' => $stats,
        ]);
    }
}