<?php
// Modules/RealEstate/Services/FileService.php

namespace Modules\RealEstate\Services;

use Modules\RealEstate\Models\File;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\Project;
use Modules\Package\Services\StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    protected $storageService;
    
    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }
    
    /**
     * Upload file to property
     */
    public function uploadToProperty(
        Property $property,
        UploadedFile $file,
        string $usageType = 'gallery',
        array $options = []
    ): File {
        $user = $property->user;
        
        // Kiểm tra storage limits
        $this->storageService->checkStorageBeforeUpload($user, $file);
        
        // Kiểm tra số lượng files của property
        $fileCount = $property->files()->count();
        $maxFiles = $user->activePackage->package->limits['maxFilesPerListing'] ?? 50;
        
        if ($fileCount >= $maxFiles) {
            throw new \Exception("Maximum {$maxFiles} files per property exceeded");
        }
        
        DB::beginTransaction();
        
        try {
            // Upload và xử lý file
            $uploadResult = $this->storageService->handleUpload(
                $user, 
                $file, 
                $property, 
                $this->getFileCategory($usageType)
            );
            
            // Tạo file record
            $fileRecord = File::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'user_package_id' => $user->activePackage->id,
                'fileable_type' => Property::class,
                'fileable_id' => $property->id,
                'file_category' => $this->getFileCategory($usageType),
                'file_type' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'disk' => 'public',
                'path' => $uploadResult['path'],
                'thumbnail_path' => $uploadResult['thumbnail_path'] ?? null,
                'original_name' => $file->getClientOriginalName(),
                'file_name' => basename($uploadResult['path']),
                'size_bytes' => $file->getSize(),
                'optimized_size_bytes' => $uploadResult['optimized_size'],
                'is_optimized' => $uploadResult['optimization_ratio'] > 0,
                'optimization_ratio' => $uploadResult['ratio'],
                'optimization_status' => 'completed',
                'has_watermark' => $uploadResult['has_watermark'] ?? false,
                'width' => $uploadResult['width'] ?? null,
                'height' => $uploadResult['height'] ?? null,
                'duration' => $uploadResult['duration'] ?? null,
                'visibility' => $options['visibility'] ?? 'public',
                'expires_at' => $options['expires_at'] ?? null,
            ]);
            
            // Tạo relation với property
            $property->fileRelations()->create([
                'file_id' => $fileRecord->id,
                'order' => $options['order'] ?? $fileCount,
                'is_primary' => $options['is_primary'] ?? false,
                'is_featured' => $options['is_featured'] ?? false,
                'usage_type' => $usageType,
                'caption' => $options['caption'] ?? null,
                'description' => $options['description'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);
            
            // Nếu là primary, cập nhật property
            if ($options['is_primary'] ?? false) {
                $property->update(['primary_image_id' => $fileRecord->id]);
            }
            
            DB::commit();
            
            return $fileRecord;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Upload file to project
     */
    public function uploadToProject(
        Project $project,
        UploadedFile $file,
        string $usageType = 'master_plan',
        array $options = []
    ): File {
        $user = auth()->user();
        
        // Kiểm tra storage limits
        $this->storageService->checkStorageBeforeUpload($user, $file);
        
        DB::beginTransaction();
        
        try {
            // Upload và xử lý file
            $uploadResult = $this->storageService->handleUpload(
                $user, 
                $file, 
                $project, 
                $this->getFileCategory($usageType)
            );
            
            // Tạo file record
            $fileRecord = File::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'user_package_id' => $user->activePackage->id,
                'fileable_type' => Project::class,
                'fileable_id' => $project->id,
                'file_category' => $this->getFileCategory($usageType),
                'file_type' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'disk' => 'public',
                'path' => $uploadResult['path'],
                'thumbnail_path' => $uploadResult['thumbnail_path'] ?? null,
                'original_name' => $file->getClientOriginalName(),
                'file_name' => basename($uploadResult['path']),
                'size_bytes' => $file->getSize(),
                'optimized_size_bytes' => $uploadResult['optimized_size'],
                'is_optimized' => $uploadResult['optimization_ratio'] > 0,
                'optimization_ratio' => $uploadResult['ratio'],
                'optimization_status' => 'completed',
                'width' => $uploadResult['width'] ?? null,
                'height' => $uploadResult['height'] ?? null,
                'duration' => $uploadResult['duration'] ?? null,
                'visibility' => $options['visibility'] ?? 'public',
            ]);
            
            // Tạo relation với project
            $project->files()->attach($fileRecord->id, [
                'order' => $options['order'] ?? 0,
                'usage_type' => $usageType,
                'caption' => $options['caption'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);
            
            DB::commit();
            
            return $fileRecord;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Delete file
     */
    public function deleteFile(File $file): bool
    {
        DB::beginTransaction();
        
        try {
            // Xóa file khỏi storage
            Storage::disk($file->disk)->delete($file->path);
            if ($file->thumbnail_path) {
                Storage::disk($file->disk)->delete($file->thumbnail_path);
            }
            
            // Xóa conversions
            foreach ($file->conversions as $conversion) {
                Storage::disk($file->disk)->delete($conversion->path);
            }
            
            // Xóa các relations
            $file->propertyRelation()->delete();
            $file->projectRelation()->delete();
            
            // Cập nhật storage usage
            $this->storageService->updateStorageAfterDelete($file);
            
            // Soft delete file
            $file->delete();
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Set file as primary for property
     */
    public function setPrimaryImage(Property $property, File $file): void
    {
        // Reset all primary flags
        $property->fileRelations()->update(['is_primary' => false]);
        
        // Set new primary
        $property->fileRelations()
            ->where('file_id', $file->id)
            ->update(['is_primary' => true]);
        
        // Update property
        $property->update(['primary_image_id' => $file->id]);
    }
    
    /**
     * Reorder property files
     */
    public function reorderFiles(Property $property, array $fileIds): void
    {
        foreach ($fileIds as $order => $fileId) {
            $property->fileRelations()
                ->where('file_id', $fileId)
                ->update(['order' => $order]);
        }
    }
    
    /**
     * Get file category from usage type
     */
    protected function getFileCategory(string $usageType): string
    {
        $categoryMap = [
            'gallery' => 'image',
            'floor_plan' => 'image',
            'video_tour' => 'video',
            'virtual_tour' => 'virtual_tour',
            'legal' => 'document',
            'certificate' => 'document',
            'marketing' => 'image',
            'master_plan' => 'image',
            'architecture' => 'image',
            'construction' => 'image',
            'other' => 'other',
        ];
        
        return $categoryMap[$usageType] ?? 'image';
    }
}