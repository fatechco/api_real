<?php
namespace Modules\Package\Services;

use App\Models\User;
use Modules\Package\Models\UserStorageUsage;
use Modules\Package\Models\UserPackage;
use Modules\RealEstate\Models\Property;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
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
     * Tối ưu file dựa trên loại và giới hạn gói
     */
    protected function optimizeFile(UploadedFile $file, string $type, array $limits): array
    {
        $result = [
            'file' => $file,
            'optimized_size' => $file->getSize(),
            'ratio' => 0,
            'thumbnail_path' => null
        ];
        
        if ($type === 'image') {
            $image = $this->imageManager->read($file->getPathname());
            
            // Resize nếu vượt quá resolution limit
            if ($limits['maxImageResolution'] && 
                ($image->width() > $limits['maxImageResolution'] || 
                 $image->height() > $limits['maxImageResolution'])) {
                $image->resize($limits['maxImageResolution'], $limits['maxImageResolution'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Nén ảnh
            $quality = $this->getQualityByPackage($limits);
            $encoded = $image->encode(null, $quality);
            $result['file'] = $encoded;
            $result['optimized_size'] = strlen((string) $encoded);
            $result['ratio'] = round((1 - $result['optimized_size'] / $file->getSize()) * 100, 2);
            
            // Tạo thumbnail
            $result['thumbnail_path'] = $this->createThumbnail($image);
        }
        
       /* if ($type === 'video' && $limits['videoDuration'] > 0) {
            // Cắt video nếu quá dài
            $video = $this->ffmpeg->open($file->getPathname());
            $duration = $video->getStreams()->videos()->first()->get('duration');
            
            if ($duration > $limits['videoDuration']) {
                // Cắt video
                $clip = $video->clip(FFMpeg\Coordinate\TimeCode::fromSeconds(0), 
                                     FFMpeg\Coordinate\TimeCode::fromSeconds($limits['videoDuration']));
                $result['file'] = $clip;
                $result['optimized_size'] = $this->getFileSize($clip);
            }
            
            // Tạo thumbnail từ video
            $result['thumbnail_path'] = $this->extractVideoThumbnail($video);
        }*/
        
        return $result;
    }
    
    /**
     * Cập nhật storage usage
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
        // Member basic: nén nhiều (quality 70)
        // VIP/Agent: quality 85
        // Agency: quality 90
        
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
            'mime_type' => 'image/jpeg', // should get from file
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