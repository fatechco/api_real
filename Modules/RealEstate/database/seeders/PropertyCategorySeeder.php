<?php

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropertyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'villa',
                'icon' => 'fas fa-building',
                'order' => 1,
                'translations' => [
                    'en' => ['name' => 'Villa', 'description' => 'Luxury standalone villas with private amenities'],
                    'vi' => ['name' => 'Biệt thự', 'description' => 'Biệt thự cao cấp với tiện ích riêng'],
                ]
            ],
            [
                'slug' => 'apartment',
                'icon' => 'fas fa-city',
                'order' => 2,
                'translations' => [
                    'en' => ['name' => 'Apartment', 'description' => 'Modern apartments and condominiums'],
                    'vi' => ['name' => 'Căn hộ', 'description' => 'Căn hộ chung cư hiện đại'],
                ]
            ],
            [
                'slug' => 'townhouse',
                'icon' => 'fas fa-warehouse',
                'order' => 3,
                'translations' => [
                    'en' => ['name' => 'Townhouse', 'description' => 'Attached houses in residential communities'],
                    'vi' => ['name' => 'Nhà phố', 'description' => 'Nhà phố liền kề trong khu dân cư'],
                ]
            ],
            [
                'slug' => 'land',
                'icon' => 'fas fa-map-marker-alt',
                'order' => 4,
                'translations' => [
                    'en' => ['name' => 'Land', 'description' => 'Vacant land and development sites'],
                    'vi' => ['name' => 'Đất nền', 'description' => 'Đất trống, đất dự án'],
                ]
            ],
            [
                'slug' => 'commercial',
                'icon' => 'fas fa-store',
                'order' => 5,
                'translations' => [
                    'en' => ['name' => 'Commercial', 'description' => 'Commercial spaces for business'],
                    'vi' => ['name' => 'Thương mại', 'description' => 'Mặt bằng kinh doanh, văn phòng'],
                ]
            ],
            [
                'slug' => 'office',
                'icon' => 'fas fa-building',
                'order' => 6,
                'translations' => [
                    'en' => ['name' => 'Office Space', 'description' => 'Office buildings and workspaces'],
                    'vi' => ['name' => 'Văn phòng', 'description' => 'Tòa nhà văn phòng, không gian làm việc'],
                ]
            ],
            [
                'slug' => 'warehouse',
                'icon' => 'fas fa-warehouse',
                'order' => 7,
                'translations' => [
                    'en' => ['name' => 'Warehouse', 'description' => 'Storage and distribution facilities'],
                    'vi' => ['name' => 'Kho bãi', 'description' => 'Kho lưu trữ, trung tâm phân phối'],
                ]
            ],
            [
                'slug' => 'duplex',
                'icon' => 'fas fa-home',
                'order' => 8,
                'translations' => [
                    'en' => ['name' => 'Duplex', 'description' => 'Two-family homes with separate units'],
                    'vi' => ['name' => 'Nhà song lập', 'description' => 'Nhà cho hai gia đình với các đơn vị riêng biệt'],
                ]
            ],
            [
                'slug' => 'penthouse',
                'icon' => 'fas fa-crown',
                'order' => 9,
                'translations' => [
                    'en' => ['name' => 'Penthouse', 'description' => 'Luxury top-floor apartments with panoramic views'],
                    'vi' => ['name' => 'Penthouse', 'description' => 'Căn hộ cao cấp tầng thượng với view toàn cảnh'],
                ]
            ],
            [
                'slug' => 'farm',
                'icon' => 'fas fa-tractor',
                'order' => 10,
                'translations' => [
                    'en' => ['name' => 'Farm', 'description' => 'Agricultural properties and farmland'],
                    'vi' => ['name' => 'Trang trại', 'description' => 'Bất động sản nông nghiệp, đất canh tác'],
                ]
            ],
        ];
        
        foreach ($categories as $category) {
            // Insert category
            $categoryId = DB::table('property_categories')->insertGetId([
                'slug' => $category['slug'],
                'icon' => $category['icon'],
                'parent_id' => null,
                'order' => $category['order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Insert translations
            foreach ($category['translations'] as $locale => $translation) {
                DB::table('property_category_translations')->insert([
                    'category_id' => $categoryId,
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }
        }
    }
}