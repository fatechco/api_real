<?php
namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PropertyFileController extends Controller
{
    protected FileService $fileService;
    
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }
    
    /**
     * Get all files of a property
     */
    public function index(Property $property, Request $request): JsonResponse
    {
        try {
            // Check permission
            if ($property->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to view these files'
                ], 403);
            }
            
            $usageType = $request->input('usage_type');
            
            $query = DB::table('files')
                ->where('fileable_type', 'Modules\\RealEstate\\Models\\Property')
                ->where('fileable_id', $property->id)
                ->where('status', 'active');
            
            if ($usageType) {
                $query->where('usage_type', $usageType);
            }
            
            $files = $query->orderBy('order')
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'uuid' => $file->uuid,
                        'url' => asset('storage/' . $file->path),
                        'thumbnail_url' => $file->thumbnail_path ? asset('storage/' . $file->thumbnail_path) : null,
                        'original_name' => $file->original_name,
                        'file_category' => $file->file_category,
                        'usage_type' => $file->usage_type,
                        'size' => $this->formatBytes($file->size_bytes),
                        'order' => $file->order,
                        'is_primary' => (bool) $file->is_primary,
                        'caption' => $file->caption,
                        'created_at' => $file->created_at,
                    ];
                });
            
            // Get primary image
            $primaryImage = $files->firstWhere('is_primary', true);
            
            return response()->json([
                'status' => true,
                'data' => [
                    'files' => $files,
                    'primary_image' => $primaryImage,
                    'total' => $files->count(),
                    'statistics' => $this->getFileStatistics($files),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Upload file to property
     */
    public function upload(Property $property, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:204800', // 200MB max
                'usage_type' => 'required|in:gallery,floor_plan,legal,video_tour,virtual_tour,marketing,certificate',
                'caption' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_primary' => 'nullable|boolean',
                'metadata' => 'nullable|array',
            ]);
            
            // Check permission
            if ($property->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to upload files to this property'
                ], 403);
            }
            
            $file = $request->file('file');
            $usageType = $request->input('usage_type');
            $options = [
                'usage_type' => $usageType,
                'caption' => $request->input('caption'),
                'description' => $request->input('description'),
                'is_primary' => $request->input('is_primary', false),
                'metadata' => $request->input('metadata', []),
            ];
            
            $uploadedFile = $this->fileService->uploadToProperty($property, $file, $usageType, $options);
            
            return response()->json([
                'status' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'id' => $uploadedFile->id,
                    'uuid' => $uploadedFile->uuid,
                    'url' => $uploadedFile->url,
                    'thumbnail_url' => $uploadedFile->thumbnail_url,
                    'original_name' => $uploadedFile->original_name,
                    'size' => $uploadedFile->size_formatted,
                    'usage_type' => $usageType,
                    'is_primary' => $options['is_primary'],
                    'caption' => $options['caption'],
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Upload multiple files to property
     */
    public function uploadMultiple(Property $property, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'file|max:204800',
                'usage_type' => 'required|in:gallery,floor_plan,legal,video_tour,virtual_tour,marketing,certificate',
            ]);
            
            // Check permission
            if ($property->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to upload files to this property'
                ], 403);
            }
            
            $usageType = $request->input('usage_type');
            $uploadedFiles = [];
            $errors = [];
            
            foreach ($request->file('files') as $index => $file) {
                try {
                    $options = [
                        'usage_type' => $usageType,
                        'order' => $index,
                        'is_primary' => $index === 0 && $usageType === 'gallery',
                    ];
                    
                    $uploadedFile = $this->fileService->uploadToProperty($property, $file, $usageType, $options);
                    $uploadedFiles[] = [
                        'id' => $uploadedFile->id,
                        'name' => $uploadedFile->original_name,
                        'url' => $uploadedFile->url,
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ];
                }
            }
            
            return response()->json([
                'status' => true,
                'message' => count($uploadedFiles) . ' files uploaded successfully',
                'data' => [
                    'uploaded' => $uploadedFiles,
                    'failed' => $errors,
                    'total' => count($uploadedFiles),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update file metadata
     */
    public function update(Property $property, $fileId, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'caption' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'order' => 'nullable|integer',
                'is_primary' => 'nullable|boolean',
                'usage_type' => 'nullable|in:gallery,floor_plan,legal,video_tour,virtual_tour,marketing,certificate',
            ]);
            
            $file = DB::table('files')
                ->where('id', $fileId)
                ->where('fileable_type', 'Modules\\RealEstate\\Entities\\Property')
                ->where('fileable_id', $property->id)
                ->first();
            
            if (!$file) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ], 404);
            }
            
            $updateData = [];
            
            if ($request->has('caption')) $updateData['caption'] = $request->caption;
            if ($request->has('description')) $updateData['description'] = $request->description;
            if ($request->has('order')) $updateData['order'] = $request->order;
            if ($request->has('is_primary')) $updateData['is_primary'] = $request->is_primary;
            if ($request->has('usage_type')) $updateData['usage_type'] = $request->usage_type;
            
            if (!empty($updateData)) {
                DB::table('files')->where('id', $fileId)->update($updateData);
            }
            
            // If setting as primary, reset other primary flags
            if ($request->input('is_primary') === true) {
                DB::table('files')
                    ->where('fileable_type', 'Modules\\RealEstate\\Models\\Property')
                    ->where('fileable_id', $property->id)
                    ->where('id', '!=', $fileId)
                    ->update(['is_primary' => false]);
                
                // Update property primary image
                DB::table('properties')
                    ->where('id', $property->id)
                    ->update(['primary_image_id' => $fileId]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'File updated successfully',
                'data' => $updateData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete file from property
     */
    public function destroy(Property $property, $fileId): JsonResponse
    {
        try {
            $file = DB::table('files')
                ->where('id', $fileId)
                ->where('fileable_type', 'Modules\\RealEstate\\Models\\Property')
                ->where('fileable_id', $property->id)
                ->first();
            
            if (!$file) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ], 404);
            }
            
            // Check permission
            if ($property->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to delete this file'
                ], 403);
            }
            
            // Delete file record and physical file
            $fileRecord = \Modules\RealEstate\Models\File::find($fileId);
            if ($fileRecord) {
                $this->fileService->deleteFile($fileRecord);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'File deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get file statistics for property
     */
    private function getFileStatistics($files): array
    {
        $stats = [
            'total' => $files->count(),
            'by_type' => [
                'images' => $files->where('file_category', 'image')->count(),
                'videos' => $files->where('file_category', 'video')->count(),
                'documents' => $files->where('file_category', 'document')->count(),
            ],
            'total_size' => $this->formatBytes($files->sum('size_bytes')),
        ];
        
        return $stats;
    }
    
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}