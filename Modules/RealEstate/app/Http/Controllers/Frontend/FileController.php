<?php
namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Models\File;
use Modules\RealEstate\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    protected FileService $fileService;
    
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }
    
    /**
     * Download file
     */
    public function download(File $file): JsonResponse
    {
        try {
            // Check permission
            if ($file->visibility === 'private' && $file->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to download this file'
                ], 403);
            }
            
            // Increment download count
            $file->incrementDownload(auth()->id(), request()->ip());
            
            $path = Storage::disk($file->disk)->path($file->path);
            
            if (!file_exists($path)) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ], 404);
            }
            
            return response()->download($path, $file->original_name, [
                'Content-Type' => $file->mime_type,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get file info
     */
    public function show(File $file): JsonResponse
    {
        try {
            // Check permission
            if ($file->visibility === 'private' && $file->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to view this file'
                ], 403);
            }
            
            // Increment view count
            $file->incrementView(auth()->id(), request()->ip());
            
            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $file->id,
                    'uuid' => $file->uuid,
                    'url' => $file->url,
                    'thumbnail_url' => $file->thumbnail_url,
                    'original_name' => $file->original_name,
                    'file_category' => $file->file_category,
                    'file_type' => $file->file_type,
                    'size' => $file->size_formatted,
                    'width' => $file->width,
                    'height' => $file->height,
                    'duration' => $file->duration,
                    'caption' => $file->caption,
                    'description' => $file->description,
                    'metadata' => $file->metadata,
                    'download_count' => $file->download_count,
                    'view_count' => $file->view_count,
                    'created_at' => $file->created_at,
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
     * Delete file
     */
    public function destroy(File $file): JsonResponse
    {
        try {
            // Check permission
            if ($file->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to delete this file'
                ], 403);
            }
            
            $this->fileService->deleteFile($file);
            
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
     * Set file as primary for property
     */
    public function setPrimary(File $file, Request $request): JsonResponse
    {
        try {
            $propertyId = $request->input('property_id');
            
            if (!$propertyId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Property ID is required'
                ], 400);
            }
            
            // Check if file belongs to property
            if ($file->fileable_type !== 'Modules\\RealEstate\\Entities\\Property' || 
                $file->fileable_id != $propertyId) {
                return response()->json([
                    'status' => false,
                    'message' => 'File does not belong to this property'
                ], 400);
            }
            
            // Reset all primary flags for this property
            DB::table('files')
                ->where('fileable_type', 'Modules\\RealEstate\\Entities\\Property')
                ->where('fileable_id', $propertyId)
                ->update(['is_primary' => false]);
            
            // Set new primary
            $file->update(['is_primary' => true]);
            
            // Update property primary image
            DB::table('properties')
                ->where('id', $propertyId)
                ->update(['primary_image_id' => $file->id]);
            
            return response()->json([
                'status' => true,
                'message' => 'File set as primary successfully',
                'data' => [
                    'file_id' => $file->id,
                    'is_primary' => true,
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
     * Reorder files for property
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_ids' => 'required|array',
                'file_ids.*' => 'exists:files,id',
                'property_id' => 'required|exists:properties,id',
            ]);
            
            $propertyId = $request->input('property_id');
            $fileIds = $request->input('file_ids');
            
            foreach ($fileIds as $index => $fileId) {
                DB::table('files')
                    ->where('id', $fileId)
                    ->where('fileable_type', 'Modules\\RealEstate\\Entities\\Property')
                    ->where('fileable_id', $propertyId)
                    ->update(['order' => $index]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Files reordered successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get file usage logs
     */
    public function logs(File $file): JsonResponse
    {
        try {
            $logs = $file->usageLogs()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'user_name' => $log->user?->name,
                        'ip_address' => $log->ip_address,
                        'created_at' => $log->created_at,
                    ];
                });
            
            return response()->json([
                'status' => true,
                'data' => $logs
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}