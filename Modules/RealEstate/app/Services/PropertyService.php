<?php

namespace Modules\RealEstate\Services;

use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\File;
use Modules\RealEstate\Models\PropertyTranslation;
use Modules\RealEstate\Repositories\PropertyRepository;
use Modules\Package\Services\PackageService;
use Modules\Package\Services\CreditService;
use Modules\Package\Services\StorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyService
{
    public function __construct(
        protected PropertyRepository $repository,
        protected PackageService $packageService,
        protected CreditService $creditService,
        protected StorageService $storageService
    ) {}

    /**
     * Create new property
     */
    public function create(array $data): array
    {
        try {
             $this->packageService->assignFreePackage(auth()->user()->id);
            return DB::transaction(function () use ($data) {
                $user = auth()->user();

                // Check package limits
                /*$check = $this->packageService->canCreateListing($user->id);
                if (!$check['can']) {
                    return [
                        'status' => false,
                        'message' => $check['reason']
                    ];
                }*/

                $translations = $data['translations'] ?? [];

                $slug = Str::slug($translations['en']['title'] ?? 'property');
                      // Dữ liệu để tạo property
            $propertyData = [
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'category_id' => $data['category_id'] ?? null,
                'slug' => $slug,
                'price' => $data['price'],
                'is_negotiable' => $data['is_negotiable'] ?? false,
                'area' => $data['area'],
                'land_area' => $data['land_area'] ?? null,
                'built_area' => $data['built_area'] ?? null,
                'bedrooms' => $data['bedrooms'] ?? null,
                'bathrooms' => $data['bathrooms'] ?? null,
                'floors' => $data['floors'] ?? null,
                'garages' => $data['garages'] ?? null,
                'year_built' => $data['year_built'] ?? null,
                'furnishing' => $data['furnishing'] ?? null,
                'legal_status' => $data['legal_status'] ?? null,
                'ownership_type' => $data['ownership_type'] ?? null,
                'country_id' => $data['country_id'] ?? null,
                'province_id' => $data['province_id'] ?? null,
                'district_id' => $data['district_id'] ?? null,
                'ward_id' => $data['ward_id'] ?? null,
                'street' => $data['street'] ?? null,
                'street_number' => $data['street_number'] ?? null,
                'building_name' => $data['building_name'] ?? null,
                'full_address' => $data['full_address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'type' => $data['type'],
                'is_featured' => $data['is_featured'] ?? false,
                'is_vip' => $data['is_vip'] ?? false,
                'is_urgent' => $data['is_urgent'] ?? false,
                'is_top' => $data['is_top'] ?? false,
                'video_url' => $data['video_url'] ?? null,
                'virtual_tour_url' => $data['virtual_tour_url'] ?? null,
                'published_at' => now(),
                ];



                 \Log::info('Property data to create: ', $propertyData);
            
                // Create property
                $property = Property::create($propertyData);
                
                // Debug sau khi tạo
                \Log::info('Property created with ID: ' . $property->id);
                \Log::info('Property exists in DB: ' . ($property->exists ? 'YES' : 'NO'));

               foreach ($translations as $locale => $translation) {
                    if (is_array($translation) && isset($translation['title'])) {
                        \Log::info("Creating translation for locale: {$locale}", [
                            'property_id' => $property->id,
                            'title' => $translation['title'],
                            'description' => $translation['description'] ?? ''
                        ]);
                        
                        PropertyTranslation::create([
                            'property_id' => $property->id,     
                            'locale' => $locale,               
                            'title' => $translation['title'],
                            'description' => $translation['description'] ?? '',
                            'content' => $translation['content'] ?? null,
                        ]);
                    }
                }

                // Upload images to File table
                if (isset($data['images']) && is_array($data['images'])) {
                    $this->uploadImagesToFileTable($property, $data['images']);
                }

                // Sync amenities
                if (isset($data['amenities'])) {
                     $property->amenities()->sync($data['amenities'], false);
                }

                // Handle VIP
                /*if (data_get($data, 'is_vip')) {
                    $vipDays = data_get($data, 'vip_days', 7);
                    $property->update([
                        'is_vip' => true,
                        'vip_expires_at' => now()->addDays($vipDays)
                    ]);

                    $this->creditService->useCreditsWithPriority(
                        $user->id,
                        2,
                        'property',
                        $property->id
                    );
                }*/

                // Handle Top
                /*if (data_get($data, 'is_top')) {
                    $topDays = data_get($data, 'top_days', 7);
                    $property->update([
                        'is_top' => true,
                        'top_expires_at' => now()->addDays($topDays)
                    ]);

                    $this->creditService->useCreditsWithPriority(
                        $user->id,
                        3,
                        'property',
                        $property->id
                    );
                }

                // Record listing usage
                $this->packageService->recordListingUsage(
                    $user->id,
                    $property->id,
                    data_get($data, 'is_vip', false)
                );
                */
                return [
                    'status' => true,
                    'data' => $property->load(['translations', 'images', 'amenities'])
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Property creation failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }


     /**
     * Update property
     */
    public function update(int $id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $user = auth()->user();
                
                // Find property
                $property = Property::find($id);
                
                if (!$property) {
                    return [
                        'status' => false,
                        'message' => 'Property not found'
                    ];
                }
                
                // Check permission
                if (!$property->canEdit($user->id)) {
                    return [
                        'status' => false,
                        'message' => 'You do not have permission to edit this property'
                    ];
                }
                
                // Get translations
                $translations = $data['translations'] ?? [];
                
                // Update slug if title changed
                if (isset($translations['en']['title'])) {
                    $newSlug = Str::slug($translations['en']['title']);
                    if ($property->slug !== $newSlug) {
                        $data['slug'] = $newSlug;
                    }
                }
                
                // Prepare update data
                $updateData = [
                    'category_id' => $data['category_id'] ?? $property->category_id,
                    'type' => $data['type'] ?? $property->type,
                    'status' => $data['status'] ?? $property->status,
                    'price' => $data['price'] ?? $property->price,
                    'is_negotiable' => $data['is_negotiable'] ?? $property->is_negotiable,
                    'area' => $data['area'] ?? $property->area,
                    'land_area' => $data['land_area'] ?? $property->land_area,
                    'built_area' => $data['built_area'] ?? $property->built_area,
                    'bedrooms' => $data['bedrooms'] ?? $property->bedrooms,
                    'bathrooms' => $data['bathrooms'] ?? $property->bathrooms,
                    'floors' => $data['floors'] ?? $property->floors,
                    'garages' => $data['garages'] ?? $property->garages,
                    'year_built' => $data['year_built'] ?? $property->year_built,
                    'furnishing' => $data['furnishing'] ?? $property->furnishing,
                    'legal_status' => $data['legal_status'] ?? $property->legal_status,
                    'ownership_type' => $data['ownership_type'] ?? $property->ownership_type,
                    'country_id' => $data['country_id'] ?? $property->country_id,
                    'province_id' => $data['province_id'] ?? $property->province_id,
                    'district_id' => $data['district_id'] ?? $property->district_id,
                    'ward_id' => $data['ward_id'] ?? $property->ward_id,
                    'street' => $data['street'] ?? $property->street,
                    'street_number' => $data['street_number'] ?? $property->street_number,
                    'building_name' => $data['building_name'] ?? $property->building_name,
                    'full_address' => $data['full_address'] ?? $property->full_address,
                    'latitude' => $data['latitude'] ?? $property->latitude,
                    'longitude' => $data['longitude'] ?? $property->longitude,
                    'video_url' => $data['video_url'] ?? $property->video_url,
                    'virtual_tour_url' => $data['virtual_tour_url'] ?? $property->virtual_tour_url,
                    'is_featured' => $data['is_featured'] ?? $property->is_featured,
                    'is_vip' => $data['is_vip'] ?? $property->is_vip,
                    'is_urgent' => $data['is_urgent'] ?? $property->is_urgent,
                    'is_top' => $data['is_top'] ?? $property->is_top,
                ];
                
                // Remove null values
                $updateData = array_filter($updateData, function ($value) {
                    return $value !== null;
                });
                
                // Add slug if changed
                if (isset($data['slug'])) {
                    $updateData['slug'] = $data['slug'];
                }
                
                // Update property
                $property->update($updateData);
                
                // Update translations
                foreach ($translations as $locale => $translation) {
                    if (is_array($translation) && isset($translation['title'])) {
                        PropertyTranslation::updateOrCreate(
                            [
                                'property_id' => $property->id,
                                'locale' => $locale
                            ],
                            [
                                'title' => $translation['title'],
                                'description' => $translation['description'] ?? '',
                                'content' => $translation['content'] ?? null,
                            ]
                        );
                    }
                }
                
                // Handle new images
                if (isset($data['images']) && is_array($data['images']) && count($data['images']) > 0) {
                    $this->uploadImagesToFileTable($property, $data['images']);
                }
                
                // Handle existing images (keep IDs)
                if (isset($data['existing_images']) && is_array($data['existing_images'])) {
                    $keepIds = array_map('intval', $data['existing_images']);
                    // Delete images not in keep list
                    $property->images()
                        ->whereNotIn('id', $keepIds)
                        ->each(function ($image) {
                            Storage::disk($image->disk)->delete($image->path);
                            if ($image->thumbnail_path) {
                                Storage::disk($image->disk)->delete($image->thumbnail_path);
                            }
                            $image->delete();
                        });
                }
                
                // Handle deleted images
                if (isset($data['deleted_images']) && is_array($data['deleted_images'])) {
                    foreach ($data['deleted_images'] as $imageId) {
                        $image = File::find($imageId);
                        if ($image) {
                            Storage::disk($image->disk)->delete($image->path);
                            if ($image->thumbnail_path) {
                                Storage::disk($image->disk)->delete($image->thumbnail_path);
                            }
                            $image->delete();
                            
                            // Delete relation
                            DB::table('property_file_relations')
                                ->where('property_id', $property->id)
                                ->where('file_id', $imageId)
                                ->delete();
                        }
                    }
                }
                
                // Sync amenities
                if (isset($data['amenities']) && is_array($data['amenities'])) {
                    $property->amenities()->sync($data['amenities']);
                }
                
                return [
                    'status' => true,
                    'data' => $property->load(['translations', 'images', 'amenities'])
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Property update failed: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    

    /**
     * Upload images to File table (centralized storage)
     */
    protected function uploadImagesToFileTable(Property $property, array $images): void
    {
        $user = auth()->user();
        
        foreach ($images as $index => $image) {
            // Check storage limit before upload
           // $this->storageService->checkStorageBeforeUpload($user, $image);
            
            // Process and optimize image
            $uploadResult = $this->storageService->handleUpload(
                $user,
                $image,
                $property,
                'image'
            );
            
             $optimizationRatio = $uploadResult['optimization_ratio'] ?? 0;
            // Create file record
            $file = File::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'user_package_id' => $user->activePackage->id,
                'fileable_type' => Property::class,
                'fileable_id' => $property->id,
                'file_category' => 'image',
                'file_type' => $image->getClientOriginalExtension(),
                'mime_type' => $image->getMimeType(),
                'disk' => 'public',
                'path' => $uploadResult['path'],
                'thumbnail_path' => $uploadResult['thumbnail_path'] ?? null,
                'original_name' => $image->getClientOriginalName(),
                'file_name' => basename($uploadResult['path']),
                'size_bytes' => $image->getSize(),
                'optimized_size_bytes' => $uploadResult['optimized_size'],
                'is_optimized' => $optimizationRatio > 0,
                'optimization_ratio' => $optimizationRatio,
                'optimization_status' => 'completed',
                'width' => $uploadResult['width'] ?? null,
                'height' => $uploadResult['height'] ?? null,
                'status' => 'active',
                'visibility' => 'public',
            ]);
            
            // Link file to property with relation
            DB::table('property_file_relations')->insert([
                'property_id' => $property->id,
                'file_id' => $file->id,
                'order' => $index,
                'is_primary' => $index === 0,
                'is_featured' => $index < 2,
                'usage_type' => 'gallery',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Update storage usage
           // $this->storageService->updateStorageUsage($user, $image->getSize(), 'image');
            
            // Set primary image
           /* if ($index === 0) {
                $property->update(['primary_image_id' => $file->id]);
            }*/
        }
    }

    /**
     * Delete property images from File table
     */
    protected function deletePropertyImages(Property $property): void
    {
        foreach ($property->images as $image) {
            // Delete physical file
            Storage::disk($image->disk)->delete($image->path);
            if ($image->thumbnail_path) {
                Storage::disk($image->disk)->delete($image->thumbnail_path);
            }
            
            // Update storage usage
            $this->storageService->updateStorageAfterDelete($image);
            
            // Delete file record
            $image->delete();
        }
        
        // Delete relations
        DB::table('property_file_relations')
            ->where('property_id', $property->id)
            ->delete();
    }

    /**
     * Upload single image to property
     */
    public function uploadImage(Property $property, $image, bool $isPrimary = false): array
    {
        try {
            $user = auth()->user();
            
            DB::beginTransaction();
            
            // Check storage limit
            $this->storageService->checkStorageBeforeUpload($user, $image);
            
            // Process image
            $uploadResult = $this->storageService->handleUpload(
                $user,
                $image,
                $property,
                'image'
            );
            
            // Get current max order
            $maxOrder = DB::table('property_file_relations')
                ->where('property_id', $property->id)
                ->max('order') ?? -1;
            
            // Create file record
            $file = File::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'user_package_id' => $user->activePackage->id,
                'fileable_type' => Property::class,
                'fileable_id' => $property->id,
                'file_category' => 'image',
                'file_type' => $image->getClientOriginalExtension(),
                'mime_type' => $image->getMimeType(),
                'disk' => 'public',
                'path' => $uploadResult['path'],
                'thumbnail_path' => $uploadResult['thumbnail_path'] ?? null,
                'original_name' => $image->getClientOriginalName(),
                'file_name' => basename($uploadResult['path']),
                'size_bytes' => $image->getSize(),
                'optimized_size_bytes' => $uploadResult['optimized_size'],
                'is_optimized' => $uploadResult['optimization_ratio'] > 0,
                'optimization_ratio' => $uploadResult['ratio'],
                'optimization_status' => 'completed',
                'width' => $uploadResult['width'] ?? null,
                'height' => $uploadResult['height'] ?? null,
                'status' => 'active',
                'visibility' => 'public',
            ]);
            
            // Link to property
            DB::table('property_file_relations')->insert([
                'property_id' => $property->id,
                'file_id' => $file->id,
                'order' => $maxOrder + 1,
                'is_primary' => $isPrimary,
                'is_featured' => false,
                'usage_type' => 'gallery',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Update storage usage
            $this->storageService->updateStorageUsage($user, $image->getSize(), 'image');
            
            // If set as primary, update property
            if ($isPrimary) {
                $property->update(['primary_image_id' => $file->id]);
            }
            
            DB::commit();
            
            return [
                'status' => true,
                'data' => $file
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete single image
     */
    public function deleteImage(Property $property, int $fileId): array
    {
        try {
            DB::beginTransaction();
            
            $file = File::where('id', $fileId)
                ->where('fileable_type', Property::class)
                ->where('fileable_id', $property->id)
                ->first();
            
            if (!$file) {
                return [
                    'status' => false,
                    'message' => 'Image not found'
                ];
            }
            
            // Get relation info
            $relation = DB::table('property_file_relations')
                ->where('property_id', $property->id)
                ->where('file_id', $file->id)
                ->first();
            
            $wasPrimary = $relation->is_primary ?? false;
            
            // Delete physical files
            Storage::disk($file->disk)->delete($file->path);
            if ($file->thumbnail_path) {
                Storage::disk($file->disk)->delete($file->thumbnail_path);
            }
            
            // Delete relation
            DB::table('property_file_relations')
                ->where('property_id', $property->id)
                ->where('file_id', $file->id)
                ->delete();
            
            // Update storage usage
            $this->storageService->updateStorageAfterDelete($file);
            
            // Delete file record
            $file->delete();
            
            // If deleted image was primary, set new primary
            if ($wasPrimary) {
                $newPrimary = DB::table('property_file_relations')
                    ->where('property_id', $property->id)
                    ->orderBy('order')
                    ->first();
                
                if ($newPrimary) {
                    DB::table('property_file_relations')
                        ->where('id', $newPrimary->id)
                        ->update(['is_primary' => true]);
                    
                    $property->update(['primary_image_id' => $newPrimary->file_id]);
                } else {
                    $property->update(['primary_image_id' => null]);
                }
            }
            
            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Image deleted successfully'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Set primary image
     */
    public function setPrimaryImage(Property $property, int $fileId): array
    {
        try {
            DB::transaction(function() use ($property, $fileId) {
                // Reset all primary flags
                DB::table('property_file_relations')
                    ->where('property_id', $property->id)
                    ->update(['is_primary' => false]);
                
                // Set new primary
                DB::table('property_file_relations')
                    ->where('property_id', $property->id)
                    ->where('file_id', $fileId)
                    ->update(['is_primary' => true]);
                
                // Update property
                $property->update(['primary_image_id' => $fileId]);
            });

            return ['status' => true];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Reorder images
     */
    public function reorderImages(Property $property, array $fileIds): array
    {
        try {
            foreach ($fileIds as $order => $fileId) {
                DB::table('property_file_relations')
                    ->where('property_id', $property->id)
                    ->where('file_id', $fileId)
                    ->update(['order' => $order]);
            }

            return ['status' => true];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete property (override)
     */
    public function delete(array $ids): array
    {
        try {
            return DB::transaction(function () use ($ids) {
                foreach ($ids as $id) {
                    $property = Property::find($id);
                    
                    if ($property && $property->canEdit(auth()->id())) {
                        // Delete images from File table and storage
                        $this->deletePropertyImages($property);
                        
                        // Delete translations
                        $property->translations()->delete();
                        
                        // Delete property
                        $property->delete();
                    }
                }

                return ['status' => true];
            });
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}