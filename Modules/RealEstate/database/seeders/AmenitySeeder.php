<?php

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            [
                'slug' => 'swimming-pool',
                'icon' => 'fas fa-swimming-pool',
                'order' => 1,
                'translations' => [
                    'en' => 'Swimming Pool',
                    'vi' => 'Hồ bơi',
                ]
            ],
            [
                'slug' => 'gym',
                'icon' => 'fas fa-dumbbell',
                'order' => 2,
                'translations' => [
                    'en' => 'Fitness Center',
                    'vi' => 'Phòng gym',
                ]
            ],
            [
                'slug' => 'parking',
                'icon' => 'fas fa-parking',
                'order' => 3,
                'translations' => [
                    'en' => 'Parking',
                    'vi' => 'Bãi đỗ xe',
                ]
            ],
            [
                'slug' => 'garden',
                'icon' => 'fas fa-tree',
                'order' => 4,
                'translations' => [
                    'en' => 'Garden',
                    'vi' => 'Vườn',
                ]
            ],
            [
                'slug' => 'security',
                'icon' => 'fas fa-shield-alt',
                'order' => 5,
                'translations' => [
                    'en' => '24/7 Security',
                    'vi' => 'Bảo vệ 24/7',
                ]
            ],
            [
                'slug' => 'air-conditioning',
                'icon' => 'fas fa-wind',
                'order' => 6,
                'translations' => [
                    'en' => 'Air Conditioning',
                    'vi' => 'Điều hòa',
                ]
            ],
            [
                'slug' => 'elevator',
                'icon' => 'fas fa-arrow-up',
                'order' => 7,
                'translations' => [
                    'en' => 'Elevator',
                    'vi' => 'Thang máy',
                ]
            ],
            [
                'slug' => 'balcony',
                'icon' => 'fas fa-archway',
                'order' => 8,
                'translations' => [
                    'en' => 'Balcony',
                    'vi' => 'Ban công',
                ]
            ],
            [
                'slug' => 'furnished',
                'icon' => 'fas fa-couch',
                'order' => 9,
                'translations' => [
                    'en' => 'Fully Furnished',
                    'vi' => 'Nội thất đầy đủ',
                ]
            ],
            [
                'slug' => 'pet-friendly',
                'icon' => 'fas fa-dog',
                'order' => 10,
                'translations' => [
                    'en' => 'Pet Friendly',
                    'vi' => 'Cho phép thú cưng',
                ]
            ],
            [
                'slug' => 'sauna',
                'icon' => 'fas fa-hot-tub',
                'order' => 11,
                'translations' => [
                    'en' => 'Sauna',
                    'vi' => 'Sauna',
                ]
            ],
            [
                'slug' => 'tennis-court',
                'icon' => 'fas fa-table-tennis',
                'order' => 12,
                'translations' => [
                    'en' => 'Tennis Court',
                    'vi' => 'Sân tennis',
                ]
            ],
            [
                'slug' => 'children-playground',
                'icon' => 'fas fa-child',
                'order' => 13,
                'translations' => [
                    'en' => 'Children Playground',
                    'vi' => 'Sân chơi trẻ em',
                ]
            ],
            [
                'slug' => 'concierge',
                'icon' => 'fas fa-concierge-bell',
                'order' => 14,
                'translations' => [
                    'en' => 'Concierge Service',
                    'vi' => 'Dịch vụ lễ tân',
                ]
            ],
            [
                'slug' => 'smart-home',
                'icon' => 'fas fa-microchip',
                'order' => 15,
                'translations' => [
                    'en' => 'Smart Home System',
                    'vi' => 'Hệ thống nhà thông minh',
                ]
            ],
            [
                'slug' => 'jacuzzi',
                'icon' => 'fas fa-hot-tub',
                'order' => 16,
                'translations' => [
                    'en' => 'Jacuzzi',
                    'vi' => 'Jacuzzi',
                ]
            ],
            [
                'slug' => 'bbq-area',
                'icon' => 'fas fa-utensils',
                'order' => 17,
                'translations' => [
                    'en' => 'BBQ Area',
                    'vi' => 'Khu vực BBQ',
                ]
            ],
            [
                'slug' => 'wine-cellar',
                'icon' => 'fas fa-wine-bottle',
                'order' => 18,
                'translations' => [
                    'en' => 'Wine Cellar',
                    'vi' => 'Hầm rượu',
                ]
            ],
            [
                'slug' => 'home-theater',
                'icon' => 'fas fa-film',
                'order' => 19,
                'translations' => [
                    'en' => 'Home Theater',
                    'vi' => 'Rạp chiếu phim tại nhà',
                ]
            ],
            [
                'slug' => 'solar-panels',
                'icon' => 'fas fa-solar-panel',
                'order' => 20,
                'translations' => [
                    'en' => 'Solar Panels',
                    'vi' => 'Tấm pin năng lượng mặt trời',
                ]
            ],
        ];
        
        foreach ($amenities as $amenity) {
            // Insert amenity
            $amenityId = DB::table('amenities')->insertGetId([
                'slug' => $amenity['slug'],
                'icon' => $amenity['icon'],
                'order' => $amenity['order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Insert translations
            foreach ($amenity['translations'] as $locale => $name) {
                DB::table('amenity_translations')->insert([
                    'amenity_id' => $amenityId,
                    'locale' => $locale,
                    'name' => $name
                ]);
            }
        }
    }
}