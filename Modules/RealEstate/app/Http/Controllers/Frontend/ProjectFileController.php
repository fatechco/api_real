<?php
namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Models\Project;
use Modules\RealEstate\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectFileController extends Controller
{
    protected FileService $fileService;
    
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }
    
    /**
     * Get all files of a project
     */
    public function index(Project $project, Request $request): JsonResponse
    {
        try {
            $usageType = $request->input('usage_type');
            
            $query = DB::table('files')
                ->where('fileable_type', 'Modules\\RealEstate\\Models\\Project')
                ->where('fileable_id', $project->id)
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
                        'caption' => $file->caption,
                        'created_at' => $file->created_at,
                    ];
                });
            
            return response()->json([
                'status' => true,
                'data' => [
                    'files' => $files,
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
     * Upload file to project
     */
    public function upload(Project $project, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:512000', // 500MB max for projects
                'usage_type' => 'required|in:master_plan,architecture,interior,construction,legal,marketing,certificate,other',
                'caption' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
            ]);
            
            // Check permission (only agency can upload)
            if (!auth()->user()->hasRole(['agency_basic', 'agency_premium'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only agencies can upload files to projects'
                ], 403);
            }
            
            // Check if agency owns this project
            if ($project->agency_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to upload files to this project'
                ], 403);
            }
            
            $file = $request->file('file');
            $usageType = $request->input('usage_type');
            $options = [
                'usage_type' => $usageType,
                'caption' => $request->input('caption'),
                'metadata' => $request->input('metadata', []),
            ];
            
            $uploadedFile = $this->fileService->uploadToProject($project, $file, $usageType, $options);
            
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
     * Upload multiple files to project
     */
    public function uploadMultiple(Project $project, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'file|max:512000',
                'usage_type' => 'required|in:master_plan,architecture,interior,construction,legal,marketing,certificate,other',
            ]);
            
            // Check permission
            if ($project->agency_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to upload files to this project'
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
                    ];
                    
                    $uploadedFile = $this->fileService->uploadToProject($project, $file, $usageType, $options);
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
     * Update project file metadata
     */
    public function update(Project $project, $fileId, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'caption' => 'nullable|string|max:255',
                'order' => 'nullable|integer',
                'usage_type' => 'nullable|in:master_plan,architecture,interior,construction,legal,marketing,certificate,other',
            ]);
            
            $file = DB::table('files')
                ->where('id', $fileId)
                ->where('fileable_type', 'Modules\\RealEstate\\Models\\Project')
                ->where('fileable_id', $project->id)
                ->first();
            
            if (!$file) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ], 404);
            }
            
            $updateData = [];
            
            if ($request->has('caption')) $updateData['caption'] = $request->caption;
            if ($request->has('order')) $updateData['order'] = $request->order;
            if ($request->has('usage_type')) $updateData['usage_type'] = $request->usage_type;
            
            if (!empty($updateData)) {
                DB::table('files')->where('id', $fileId)->update($updateData);
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
     * Delete file from project
     */
    public function destroy(Project $project, $fileId): JsonResponse
    {
        try {
            $file = DB::table('files')
                ->where('id', $fileId)
                ->where('fileable_type', 'Modules\\RealEstate\\Models\\Project')
                ->where('fileable_id', $project->id)
                ->first();
            
            if (!$file) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ], 404);
            }
            
            // Check permission
            if ($project->agency_id !== auth()->id()) {
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
     * Get file statistics for project
     */
    private function getFileStatistics($files): array
    {
        $stats = [
            'total' => $files->count(),
            'by_type' => [
                'master_plan' => $files->where('usage_type', 'master_plan')->count(),
                'architecture' => $files->where('usage_type', 'architecture')->count(),
                'interior' => $files->where('usage_type', 'interior')->count(),
                'construction' => $files->where('usage_type', 'construction')->count(),
                'legal' => $files->where('usage_type', 'legal')->count(),
                'marketing' => $files->where('usage_type', 'marketing')->count(),
                'certificate' => $files->where('usage_type', 'certificate')->count(),
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