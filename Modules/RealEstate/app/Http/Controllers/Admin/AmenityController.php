<?php
namespace Modules\RealEstate\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Models\Amenity;
use Modules\RealEstate\Repositories\AmenityRepository;
use Modules\RealEstate\Http\Resources\AmenityResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AmenityController extends Controller
{
    protected AmenityRepository $repository;
    
    public function __construct(AmenityRepository $repository)
    {
        $this->repository = $repository;
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|manager');
    }
    
    /**
     * Get list of amenities with pagination
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
     * Create new amenity
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'icon' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,vi,fr,zh,ko',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create amenity
            $amenity = Amenity::create([
                'slug' => Str::slug($validated['translations'][0]['name']),
                'icon' => $validated['icon'] ?? null,
                'order' => $validated['order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);
            
            // Create translations
            foreach ($validated['translations'] as $translation) {
                $amenity->translations()->create([
                    'locale' => $translation['locale'],
                    'name' => $translation['name'],
                    'description' => $translation['description'] ?? null,
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => true,
                'message' => 'Amenity created successfully',
                'data' => new AmenityResource($amenity->load('translations')),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create amenity: ' . $e->getMessage(),
            ], 500);
        }
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
            'data' => new AmenityResource($amenity->load('translations')),
        ]);
    }
    
    /**
     * Update amenity
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $amenity = Amenity::find($id);
        
        if (!$amenity) {
            return response()->json([
                'status' => false,
                'message' => 'Amenity not found',
            ], 404);
        }
        
        $validated = $request->validate([
            'icon' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'translations' => 'nullable|array',
            'translations.*.locale' => 'required|string|in:en,vi,fr,zh,ko',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Update amenity
            $amenity->update([
                'icon' => $validated['icon'] ?? $amenity->icon,
                'order' => $validated['order'] ?? $amenity->order,
                'is_active' => $validated['is_active'] ?? $amenity->is_active,
            ]);
            
            // Update translations
            if (isset($validated['translations'])) {
                foreach ($validated['translations'] as $translation) {
                    $amenity->translations()->updateOrCreate(
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
                'message' => 'Amenity updated successfully',
                'data' => new AmenityResource($amenity->load('translations')),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update amenity: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete amenity
     */
    public function destroy(int $id): JsonResponse
    {
        $amenity = Amenity::find($id);
        
        if (!$amenity) {
            return response()->json([
                'status' => false,
                'message' => 'Amenity not found',
            ], 404);
        }
        
        try {
            // Check if amenity is used in any property
            if ($amenity->properties()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete amenity because it is used by ' . $amenity->properties()->count() . ' properties',
                ], 400);
            }
            
            $amenity->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'Amenity deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete amenity: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Bulk delete amenities
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:amenities,id',
        ]);
        
        try {
            $deleted = 0;
            $failed = [];
            
            foreach ($validated['ids'] as $id) {
                $amenity = Amenity::find($id);
                if ($amenity && $amenity->properties()->count() === 0) {
                    $amenity->delete();
                    $deleted++;
                } else {
                    $failed[] = $id;
                }
            }
            
            return response()->json([
                'status' => true,
                'message' => "Deleted {$deleted} amenities",
                'data' => [
                    'deleted' => $deleted,
                    'failed' => $failed,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete amenities: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Reorder amenities
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:amenities,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);
        
        try {
            foreach ($validated['orders'] as $item) {
                Amenity::where('id', $item['id'])->update(['order' => $item['order']]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Amenities reordered successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to reorder amenities: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Toggle amenity active status
     */
    public function toggleActive(int $id): JsonResponse
    {
        $amenity = Amenity::find($id);
        
        if (!$amenity) {
            return response()->json([
                'status' => false,
                'message' => 'Amenity not found',
            ], 404);
        }
        
        $amenity->update(['is_active' => !$amenity->is_active]);
        
        return response()->json([
            'status' => true,
            'message' => $amenity->is_active ? 'Amenity activated' : 'Amenity deactivated',
            'data' => [
                'id' => $amenity->id,
                'is_active' => $amenity->is_active,
            ],
        ]);
    }
    
    /**
     * Get amenity statistics
     */
    public function statistics(): JsonResponse
    {
        $total = Amenity::count();
        $active = Amenity::where('is_active', true)->count();
        $inactive = $total - $active;
        
        $mostUsed = Amenity::withCount('properties')
            ->orderBy('properties_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($amenity) {
                return [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'properties_count' => $amenity->properties_count,
                ];
            });
        
        return response()->json([
            'status' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'most_used' => $mostUsed,
            ],
        ]);
    }
}