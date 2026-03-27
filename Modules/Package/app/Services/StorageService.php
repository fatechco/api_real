<?php
namespace Modules\Package\Services;

use Modules\RealEstate\Models\Property;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Modules\Package\Models\UserStorageUsage;
use Modules\User\Models\User;

//use FFMpeg\FFMpeg;

class StorageService
{
    protected $imageManager;
    //protected $ffmpeg;
    
    public function __construct()
    {
        $this->imageManager = ImageManager::gd();
       // $this->ffmpeg = FFMpeg::create();
    }
    
    /**
     * Kiểm tra storage trước khi upload
     */
    public function checkStorageBeforeUpload(User $user, UploadedFile $file): void
    {
        // 1. Lấy package hiện tại
        $userPackage = $user->activePackage;
        if (!$userPackage) {
            throw new \Exception('No active package found');
        }
        
        $limits = $userPackage->package->limits;
        
        // 2. Kiểm tra kích thước file
        $fileSizeMB = $file->getSize() / 1024 / 1024;
        if ($fileSizeMB > $limits['maxFileSize']) {
            throw new \Exception("File size exceeds limit. Maximum: {$limits['maxFileSize']} MB");
        }
        
        // 3. Kiểm tra dung lượng tổng
        $storageUsage = $user->storageUsage;
        $totalLimitBytes = $limits['storage'] * 1024 * 1024;
        
        if ($storageUsage->total_used_bytes + $file->getSize() > $totalLimitBytes) {
            throw new \Exception("Storage limit exceeded. Used: " . 
                $this->formatBytes($storageUsage->total_used_bytes) . " / " .
                $this->formatBytes($totalLimitBytes));
        }
    }
    
    /**
     * Kiểm tra và xử lý upload file
     */
    public function handleUpload(User $user, UploadedFile $file, $fileable, string $type): array
    {
        // 1. Lấy package hiện tại
        $userPackage = $user->activePackage;
        if (!$userPackage) {
            throw new \Exception('No active package found');
        }
        
        $limits = $userPackage->package->limits;
        
        // 2. Kiểm tra loại file
        $this->validateFileType($file, $limits['allowedFileTypes']);
        
        // 3. Kiểm tra kích thước file
        $fileSizeMB = $file->getSize() / 1024 / 1024;
        if ($fileSizeMB > $limits['maxFileSize']) {
            throw new \Exception("File size exceeds limit. Maximum: {$limits['maxFileSize']} MB");
        }
        
        // 4. Kiểm tra dung lượng tổng
        $storageUsage = $user->storageUsage;
        if ($storageUsage->total_used_bytes + $file->getSize() > $limits['storage'] * 1024 * 1024) {
            throw new \Exception("Storage limit exceeded. Used: " . 
                $this->formatBytes($storageUsage->total_used_bytes) . " / " .
                $this->formatBytes($limits['storage'] * 1024 * 1024));
        }
        
        // 5. Kiểm tra giới hạn storage cho từng tin
        if ($fileable instanceof Property) {
            $propertyUsage = $storageUsage->listing_storage_usage[$fileable->id] ?? 0;
            $storagePerListing = $limits['storagePerListing'] * 1024 * 1024;
            
            if ($propertyUsage + $file->getSize() > $storagePerListing) {
                throw new \Exception("Storage per listing limit exceeded. Maximum: " .
                    $this->formatBytes($storagePerListing));
            }
        }
        
        // 6. Xử lý và tối ưu file
        $optimized = $this->optimizeFile($file, $type, $limits);
        
        // 7. Lưu file
        $path = $this->storeFile($optimized['file'], $fileable, $type);
        
        // 8. Cập nhật storage usage
        $this->updateStorageUsage($user, $file->getSize(), $optimized['optimized_size'], $type, $fileable);
        
        return [
            'path' => $path,
            'original_size' => $file->getSize(),
            'optimized_size' => $optimized['optimized_size'],
            'optimization_ratio' => $optimized['ratio'],
            'thumbnail_path' => $optimized['thumbnail_path'] ?? null,
        ];
    }
    
    /**
     * Validate file type
     */
    protected function validateFileType(UploadedFile $file, array $allowedTypes): void
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($mimeType, $allowedTypes) && !in_array($extension, $allowedTypes)) {
            throw new \Exception("File type not allowed. Allowed: " . implode(', ', $allowedTypes));
        }
    }

    /**
     * Ensure storage usage record exists for user
     */
    public function ensureStorageUsageExists(int $userId, int $userPackageId): void
    {
        
        $exists = UserStorageUsage::where('user_id', $userId)->exists();
        
        if (!$exists) {
            UserStorageUsage::create([
                'user_id' => $userId,
                'user_package_id' => $userPackageId,
                'total_used_bytes' => 0,
                'images_bytes' => 0,
                'videos_bytes' => 0,
                'documents_bytes' => 0,
                'other_bytes' => 0,
                'total_files_count' => 0,
                'images_count' => 0,
                'videos_count' => 0,
                'documents_count' => 0,
                'other_count' => 0,
                'listing_storage_usage' => [],
                'last_reset_at' => now(),
                'last_calculated_at' => now(),
            ]);
        }
    }

/**
 * Tối ưu file dựa trên loại và giới hạn gói
 */
protected function optimizeFile(UploadedFile $file, string $type, array $limits): array
{
    // Khởi tạo mảng kết quả với đầy đủ các keys
    $result = [
        'file' => $file,
        'optimized_size' => $file->getSize(),
        'ratio' => 0,
        'thumbnail_path' => null,
        'width' => null,
        'height' => null,
    ];
    
    if ($type === 'image') {
        try {
            // Đọc ảnh
            $image = $this->imageManager->read($file->getPathname());
            
            // Lưu kích thước gốc
            $result['width'] = $image->width();
            $result['height'] = $image->height();
            
            // Resize nếu vượt quá resolution limit
            if (isset($limits['maxImageResolution']) && $limits['maxImageResolution'] > 0) {
                if ($image->width() > $limits['maxImageResolution'] || 
                    $image->height() > $limits['maxImageResolution']) {
                    $image->resize($limits['maxImageResolution'], $limits['maxImageResolution'], function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    
                    // Cập nhật kích thước sau resize
                    $result['width'] = $image->width();
                    $result['height'] = $image->height();
                }
            }
            
            // Nén ảnh - Lấy quality phù hợp
            $quality = $this->getQualityByPackage($limits);
            
            // Encode ảnh với định dạng phù hợp
            $mime = $file->getMimeType();
            $encoded = null;
            
            if ($mime === 'image/jpeg') {
                $encoded = $image->toJpeg(quality: $quality);
            } elseif ($mime === 'image/png') {
                $encoded = $image->toPng();
            } elseif ($mime === 'image/webp') {
                $encoded = $image->toWebp(quality: $quality);
            } else {
                $encoded = $image->toJpeg(quality: $quality);
            }
            
            $result['file'] = $encoded;
            $result['optimized_size'] = strlen((string) $encoded);
            
            // Tính tỷ lệ nén
            if ($file->getSize() > 0) {
                $result['ratio'] = round((1 - $result['optimized_size'] / $file->getSize()) * 100, 2);
            } else {
                $result['ratio'] = 0;
            }
            
            // Tạo thumbnail
            $result['thumbnail_path'] = $this->createThumbnail($image);
            
        } catch (\Exception $e) {
            \Log::error('Image optimization failed: ' . $e->getMessage());
            // Nếu optimization thất bại, vẫn dùng file gốc
            $result['file'] = $file;
            $result['optimized_size'] = $file->getSize();
            $result['ratio'] = 0;
            $result['width'] = null;
            $result['height'] = null;
            $result['thumbnail_path'] = null;
        }
    }
    
    // Đảm bảo các keys luôn tồn tại
    return [
        'file' => $result['file'],
        'optimized_size' => $result['optimized_size'],
        'ratio' => $result['ratio'],
        'thumbnail_path' => $result['thumbnail_path'],
        'width' => $result['width'],
        'height' => $result['height'],
    ];
}

/**
 * Create thumbnail
 */
protected function createThumbnail($image): string
{
    try {
        // Clone ảnh gốc để tạo thumbnail
        $thumbnail = clone $image;
        
        // Resize thumbnail (300x200)
        $thumbnail->scale(width: 300);
        
        // Encode thumbnail
        $thumbnailData = $thumbnail->toJpeg(quality: 70);
        
        $path = 'thumbnails/' . uniqid() . '.jpg';
        Storage::disk('public')->put($path, (string) $thumbnailData);
        
        return $path;
    } catch (\Exception $e) {
        \Log::error('Thumbnail creation failed: ' . $e->getMessage());
        return '';
    }
}

    
    /**
     * Store file
     */
    protected function storeFile($file, $fileable, string $type): string
    {
        $path = $fileable instanceof Property 
            ? "properties/{$fileable->id}/{$type}/" . uniqid() . '.jpg'
            : "uploads/{$type}/" . uniqid() . '.jpg';
        
        Storage::disk('public')->put($path, (string) $file);
        
        return $path;
    }
    
    
    /**
     * Update storage after delete
     */
    public function updateStorageAfterDelete($file): void
    {
        $user = $file->user;
        $storageUsage = $user->storageUsage;
        
        $storageUsage->decrement('total_used_bytes', $file->size_bytes);
        $storageUsage->decrement('total_files_count');
        
        $typeField = "{$file->file_category}s_bytes";
        $countField = "{$file->file_category}s_count";
        $storageUsage->decrement($typeField, $file->size_bytes);
        $storageUsage->decrement($countField);
        
        $storageUsage->save();
    }
    
    /**
     * Update storage usage
     */
    protected function updateStorageUsage(User $user, int $originalSize, int $optimizedSize, string $type, $fileable): void
    {
        $storageUsage = $user->storageUsage;
        
        // Cập nhật tổng
        $storageUsage->increment('total_used_bytes', $optimizedSize);
        $storageUsage->increment('total_files_count');
        
        // Cập nhật theo loại file
        $typeField = "{$type}s_bytes";
        $countField = "{$type}s_count";
        $storageUsage->increment($typeField, $optimizedSize);
        $storageUsage->increment($countField);
        
        // Cập nhật cho từng listing
        if ($fileable instanceof Property) {
            $listingUsage = $storageUsage->listing_storage_usage ?? [];
            $listingUsage[$fileable->id] = ($listingUsage[$fileable->id] ?? 0) + $optimizedSize;
            $storageUsage->listing_storage_usage = $listingUsage;
            $storageUsage->save();
        }
        
        // Ghi log
        $this->logUpload($user, $fileable, $type, $originalSize, $optimizedSize);
    }
    
    /**
     * Kiểm tra hạn mức storage trước khi tạo listing
     */
    public function checkStorageBeforeListing(User $user, int $estimatedFileSize): bool
    {
        $userPackage = $user->activePackage;
        $storageUsage = $user->storageUsage;
        $limit = $userPackage->package->limits['storage'] * 1024 * 1024;
        
        if ($storageUsage->total_used_bytes + $estimatedFileSize > $limit) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Lấy thông tin storage usage cho dashboard
     */
    public function getStorageStats(User $user): array
    {
        $storageUsage = $user->storageUsage;
        $userPackage = $user->activePackage;
        $limits = $userPackage->package->limits;
        
        $limitBytes = $limits['storage'] * 1024 * 1024;
        $usedPercent = ($storageUsage->total_used_bytes / $limitBytes) * 100;
        
        return [
            'total' => [
                'used' => $this->formatBytes($storageUsage->total_used_bytes),
                'limit' => $this->formatBytes($limitBytes),
                'used_bytes' => $storageUsage->total_used_bytes,
                'limit_bytes' => $limitBytes,
                'percentage' => round($usedPercent, 2),
                'remaining' => $this->formatBytes($limitBytes - $storageUsage->total_used_bytes),
            ],
            'breakdown' => [
                'images' => [
                    'size' => $this->formatBytes($storageUsage->images_bytes),
                    'count' => $storageUsage->images_count,
                ],
                'videos' => [
                    'size' => $this->formatBytes($storageUsage->videos_bytes),
                    'count' => $storageUsage->videos_count,
                ],
                'documents' => [
                    'size' => $this->formatBytes($storageUsage->documents_bytes),
                    'count' => $storageUsage->documents_count,
                ],
            ],
            'files_count' => $storageUsage->total_files_count,
            'limits' => [
                'max_file_size' => $limits['maxFileSize'] . ' MB',
                'max_files_per_listing' => $limits['maxFilesPerListing'],
                'storage_per_listing' => $this->formatBytes($limits['storagePerListing'] * 1024 * 1024),
            ],
            'warnings' => $this->getWarnings($storageUsage, $limitBytes),
        ];
    }
    
    /**
     * Cảnh báo khi sắp hết dung lượng
     */
    protected function getWarnings($storageUsage, $limitBytes): array
    {
        $warnings = [];
        $usedPercent = ($storageUsage->total_used_bytes / $limitBytes) * 100;
        
        if ($usedPercent >= 90) {
            $warnings[] = [
                'level' => 'danger',
                'message' => 'You are running out of storage. Please upgrade your plan or free up space.',
                'upgrade_url' => '/packages',
            ];
        } elseif ($usedPercent >= 75) {
            $warnings[] = [
                'level' => 'warning',
                'message' => 'You have used ' . round($usedPercent) . '% of your storage. Consider upgrading.',
            ];
        }
        
        return $warnings;
    }
    
    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Xác định quality nén dựa trên gói
     */
    protected function getQualityByPackage(array $limits): int
    {
        $storageLimit = $limits['storage'];
        if ($storageLimit <= 50) return 70;
        if ($storageLimit <= 200) return 80;
        if ($storageLimit <= 2000) return 85;
        return 90;
    }
    
    /**
     * Log upload activity
     */
    protected function logUpload(User $user, $fileable, string $type, int $originalSize, int $optimizedSize): void
    {
        \DB::table('file_upload_logs')->insert([
            'user_id' => $user->id,
            'fileable_type' => get_class($fileable),
            'fileable_id' => $fileable->id,
            'file_type' => $type,
            'mime_type' => 'image/jpeg',
            'file_size' => $optimizedSize,
            'original_name' => 'file_name',
            'storage_path' => 'path',
            'is_optimized' => $originalSize !== $optimizedSize,
            'optimization_status' => 'completed',
            'optimization_metadata' => json_encode([
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'ratio' => round((1 - $optimizedSize / $originalSize) * 100, 2)
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}