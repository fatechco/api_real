<?php
namespace Modules\RealEstate\Services;

use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\PropertyImage;
use Modules\RealEstate\Repositories\PropertyRepository;
use Modules\Package\Services\PackageService;
use Modules\Package\Services\CreditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyService
{
    public function __construct(
        protected PropertyRepository $repository,
        protected PackageService $packageService,
        protected CreditService $creditService
    ) {}

    public function create(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $user = auth()->user();

                $check = $this->packageService->canCreateListing($user->id);
                if (!$check['can']) {
                    return [
                        'status' => false,
                        'message' => $check['reason']
                    ];
                }

                $property = Property::create([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'slug' => Str::slug($data['title']['en']),
                    ...$data
                ]);

                if (isset($data['images'])) {
                    $this->uploadImages($property, $data['images']);
                }

                if (isset($data['amenities'])) {
                    $property->amenities()->sync($data['amenities']);
                }

                if (data_get($data, 'is_vip')) {
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
                }

                if (data_get($data, 'is_top')) {
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

                $this->packageService->recordListingUsage(
                    $user->id,
                    $property->id,
                    data_get($data, 'is_vip', false)
                );

                return [
                    'status' => true,
                    'data' => $property->load(['images', 'amenities'])
                ];
            });
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function update(int $id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $property = Property::find($id);

                if (!$property) {
                    return [
                        'status' => false,
                        'message' => 'Property not found'
                    ];
                }

                if (!$property->canEdit(auth()->id())) {
                    return [
                        'status' => false,
                        'message' => 'You do not have permission to edit this property'
                    ];
                }

                if (isset($data['title']) && $data['title'] !== $property->title) {
                    $data['slug'] = Str::slug($data['title']['en']);
                }

                $property->update($data);

                if (isset($data['amenities'])) {
                    $property->amenities()->sync($data['amenities']);
                }

                if (isset($data['image_ids'])) {
                    $this->reorderImages($property, $data['image_ids']);
                }

                return [
                    'status' => true,
                    'data' => $property->load(['images', 'amenities'])
                ];
            });
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function delete(array $ids): array
    {
        try {
            return DB::transaction(function () use ($ids) {
                foreach ($ids as $id) {
                    $property = Property::find($id);
                    
                    if ($property && $property->canEdit(auth()->id())) {
                        foreach ($property->images as $image) {
                            Storage::disk('public')->delete([
                                $image->path,
                                $image->thumbnail_path,
                                $image->medium_path,
                                $image->large_path
                            ]);
                            $image->delete();
                        }
                        
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

    public function uploadImages(Property $property, array $images): array
    {
        try {
            $uploaded = [];

            foreach ($images as $index => $image) {
                $path = $image->store('properties/' . $property->id, 'public');
                
                $thumbnailPath = $this->createThumbnail($image, $property->id, 'thumb');
                $mediumPath = $this->createThumbnail($image, $property->id, 'medium');
                $largePath = $this->createThumbnail($image, $property->id, 'large');

                $propertyImage = $property->images()->create([
                    'path' => $path,
                    'thumbnail_path' => $thumbnailPath,
                    'medium_path' => $mediumPath,
                    'large_path' => $largePath,
                    'order' => $property->images()->count(),
                    'is_primary' => $property->images()->count() === 0
                ]);

                $uploaded[] = $propertyImage;
            }

            return [
                'status' => true,
                'data' => $uploaded
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function reorderImages(Property $property, array $imageIds): array
    {
        try {
            foreach ($imageIds as $order => $imageId) {
                PropertyImage::where('id', $imageId)
                    ->where('property_id', $property->id)
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

    public function setPrimaryImage(Property $property, int $imageId): array
    {
        try {
            DB::transaction(function() use ($property, $imageId) {
                $property->images()->update(['is_primary' => false]);
                PropertyImage::where('id', $imageId)
                    ->where('property_id', $property->id)
                    ->update(['is_primary' => true]);
            });

            return ['status' => true];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deleteImage(Property $property, int $imageId): array
    {
        try {
            $image = PropertyImage::where('id', $imageId)
                ->where('property_id', $property->id)
                ->first();

            if ($image) {
                Storage::disk('public')->delete([
                    $image->path,
                    $image->thumbnail_path,
                    $image->medium_path,
                    $image->large_path
                ]);
                
                $image->delete();

                if ($image->is_primary) {
                    $newPrimary = $property->images()->first();
                    if ($newPrimary) {
                        $newPrimary->update(['is_primary' => true]);
                    }
                }
            }

            return ['status' => true];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function toggleFeature(int $id): array
    {
        try {
            $property = Property::find($id);

            if (!$property) {
                return [
                    'status' => false,
                    'message' => 'Property not found'
                ];
            }

            $property->update([
                'is_featured' => !$property->is_featured
            ]);

            return [
                'status' => true,
                'data' => $property
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function approve(int $id): array
    {
        try {
            $property = Property::find($id);

            if (!$property) {
                return [
                    'status' => false,
                    'message' => 'Property not found'
                ];
            }

            $property->update([
                'status' => 'available',
                'published_at' => now()
            ]);

            return [
                'status' => true,
                'data' => $property
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function reject(int $id, string $reason): array
    {
        try {
            $property = Property::find($id);

            if (!$property) {
                return [
                    'status' => false,
                    'message' => 'Property not found'
                ];
            }

            $property->update([
                'status' => 'rejected',
                'metadata->rejection_reason' => $reason
            ]);

            return [
                'status' => true,
                'data' => $property
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function createThumbnail($image, $propertyId, $size): string
    {
        $path = "properties/{$propertyId}/{$size}/" . $image->hashName();
        return $path;
    }
}