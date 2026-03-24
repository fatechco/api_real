<?php

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        
        // Get user IDs
        $userId = DB::table('users')->where('email', 'admin@gmail.com')->value('id') ?? 112;
        $agentId = DB::table('users')->where('email', 'admin@gmail.com')->value('id') ?? 112;
        $agencyId = DB::table('users')->where('email', 'admin@gmail.com')->value('id') ?? 112;
        
        // Get USA location IDs
        $usaId = DB::table('countries')->where('code', 'US')->value('id');
        $californiaId = DB::table('provinces')->where('code', 'CA')->where('country_id', $usaId)->value('id');
        $newYorkId = DB::table('provinces')->where('code', 'NY')->where('country_id', $usaId)->value('id');
        $texasId = DB::table('provinces')->where('code', 'TX')->where('country_id', $usaId)->value('id');
        $floridaId = DB::table('provinces')->where('code', 'FL')->where('country_id', $usaId)->value('id');
        
        // District IDs
        $laCountyId = DB::table('districts')->where('code', 'LA')->where('province_id', $californiaId)->value('id');
        $manhattanId = DB::table('districts')->where('code', 'MAN')->where('province_id', $newYorkId)->value('id');
        $harrisCountyId = DB::table('districts')->where('code', 'HAR')->where('province_id', $texasId)->value('id');
        $miamiDadeId = DB::table('districts')->where('code', 'MIA')->where('province_id', $floridaId)->value('id');
        $orangeCountyId = DB::table('districts')->where('code', 'ORA')->where('province_id', $californiaId)->value('id');
        
        // Ward/City IDs
        $losAngelesId = DB::table('wards')->where('code', 'LAX')->where('district_id', $laCountyId)->value('id');
        $houstonId = DB::table('wards')->where('code', 'HOU')->where('district_id', $harrisCountyId)->value('id');
        $miamiId = DB::table('wards')->where('code', 'MIA')->where('district_id', $miamiDadeId)->value('id');
        $irvineId = DB::table('wards')->where('code', 'IRV')->where('district_id', $orangeCountyId)->value('id');
        
        // Category IDs
        $villaId = DB::table('property_categories')->where('slug', 'villa')->value('id');
        $apartmentId = DB::table('property_categories')->where('slug', 'apartment')->value('id');
        $townhouseId = DB::table('property_categories')->where('slug', 'townhouse')->value('id');
        $landId = DB::table('property_categories')->where('slug', 'land')->value('id');
        
        // Define 5 properties
        $properties = [
            // Property 1: Luxury Beverly Hills Villa
            [
                'uuid' => Str::uuid(),
                'user_id' => $agencyId,
                'project_id' => null,
                'category_id' => $villaId,
                'slug' => 'luxury-villa-beverly-hills',
                'price' => 12500000,
                'price_per_m2' => 12500,
                'is_negotiable' => false,
                'area' => 1000,
                'land_area' => 2000,
                'built_area' => 800,
                'bedrooms' => 6,
                'bathrooms' => 8,
                'floors' => 2,
                'garages' => 4,
                'year_built' => 2022,
                'furnishing' => 'fully-furnished',
                'legal_status' => 'clear_title',
                'ownership_type' => 'private',
                'country_id' => $usaId,
                'province_id' => $californiaId,
                'district_id' => $laCountyId,
                'ward_id' => $losAngelesId,
                'street' => 'Beverly Drive',
                'street_number' => '1234',
                'full_address' => '1234 Beverly Drive, Los Angeles, CA 90210',
                'latitude' => 34.0736,
                'longitude' => -118.4004,
                'status' => 'available',
                'type' => 'sale',
                'is_featured' => true,
                'is_vip' => true,
                'vip_expires_at' => Carbon::now()->addDays(30),
                'is_top' => true,
                'top_expires_at' => Carbon::now()->addDays(15),
                'views' => 2500,
                'unique_views' => 1890,
                'favorites_count' => 123,
                'published_at' => Carbon::now()->subDays(10),
                'expired_at' => Carbon::now()->addDays(180),
                'created_at' => Carbon::now()->subDays(10),
                'updated_at' => Carbon::now(),
                'amenities' => ['swimming-pool', 'gym', 'garden', 'security', 'air-conditioning', 'parking', 'sauna', 'tennis-court']
            ],
            // Property 2: Manhattan Luxury Apartment
            [
                'uuid' => Str::uuid(),
                'user_id' => $userId,
                'project_id' => null,
                'category_id' => $apartmentId,
                'slug' => 'luxury-apartment-manhattan',
                'price' => 3500000,
                'price_per_m2' => 25000,
                'is_negotiable' => true,
                'area' => 140,
                'land_area' => null,
                'built_area' => 140,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'floors' => 1,
                'garages' => 1,
                'year_built' => 2021,
                'furnishing' => 'fully-furnished',
                'legal_status' => 'clear_title',
                'ownership_type' => 'condo',
                'country_id' => $usaId,
                'province_id' => $newYorkId,
                'district_id' => $manhattanId,
                'ward_id' => null,
                'street' => '5th Avenue',
                'street_number' => '721',
                'full_address' => '721 5th Avenue, Manhattan, NY 10022',
                'latitude' => 40.7625,
                'longitude' => -73.9741,
                'status' => 'available',
                'type' => 'sale',
                'is_featured' => true,
                'is_vip' => true,
                'vip_expires_at' => Carbon::now()->addDays(20),
                'is_top' => true,
                'top_expires_at' => Carbon::now()->addDays(10),
                'views' => 1850,
                'unique_views' => 1420,
                'favorites_count' => 89,
                'published_at' => Carbon::now()->subDays(5),
                'expired_at' => Carbon::now()->addDays(180),
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now(),
                'amenities' => ['gym', 'concierge', 'security', 'elevator', 'parking', 'air-conditioning']
            ],
            // Property 3: Houston Townhouse for Rent
            [
                'uuid' => Str::uuid(),
                'user_id' => $agentId,
                'project_id' => null,
                'category_id' => $townhouseId,
                'slug' => 'townhouse-houston',
                'price' => 4500,
                'price_per_m2' => null,
                'is_negotiable' => true,
                'area' => 220,
                'land_area' => 150,
                'built_area' => 220,
                'bedrooms' => 3,
                'bathrooms' => 3,
                'floors' => 2,
                'garages' => 2,
                'year_built' => 2019,
                'furnishing' => 'furnished',
                'legal_status' => 'clear_title',
                'ownership_type' => 'private',
                'country_id' => $usaId,
                'province_id' => $texasId,
                'district_id' => $harrisCountyId,
                'ward_id' => $houstonId,
                'street' => 'Westheimer Road',
                'street_number' => '2500',
                'full_address' => '2500 Westheimer Road, Houston, TX 77057',
                'latitude' => 29.7420,
                'longitude' => -95.4527,
                'status' => 'available',
                'type' => 'rent',
                'is_featured' => false,
                'is_vip' => true,
                'vip_expires_at' => Carbon::now()->addDays(15),
                'is_top' => false,
                'top_expires_at' => null,
                'views' => 620,
                'unique_views' => 410,
                'favorites_count' => 18,
                'published_at' => Carbon::now()->subDays(15),
                'expired_at' => Carbon::now()->addDays(365),
                'created_at' => Carbon::now()->subDays(15),
                'updated_at' => Carbon::now(),
                'amenities' => ['garden', 'parking', 'security', 'air-conditioning', 'furnished', 'pet-friendly']
            ],
            // Property 4: Irvine Land
            [
                'uuid' => Str::uuid(),
                'user_id' => $userId,
                'project_id' => null,
                'category_id' => $landId,
                'slug' => 'land-irvine',
                'price' => 2500000,
                'price_per_m2' => 1250,
                'is_negotiable' => true,
                'area' => 2000,
                'land_area' => 2000,
                'built_area' => 0,
                'bedrooms' => 0,
                'bathrooms' => 0,
                'floors' => 0,
                'garages' => 0,
                'year_built' => null,
                'furnishing' => 'unfurnished',
                'legal_status' => 'clear_title',
                'ownership_type' => 'private',
                'country_id' => $usaId,
                'province_id' => $californiaId,
                'district_id' => $orangeCountyId,
                'ward_id' => $irvineId,
                'street' => 'Jamboree Road',
                'street_number' => null,
                'full_address' => 'Jamboree Road, Irvine, CA 92618',
                'latitude' => 33.6846,
                'longitude' => -117.8265,
                'status' => 'available',
                'type' => 'sale',
                'is_featured' => false,
                'is_vip' => false,
                'vip_expires_at' => null,
                'is_top' => false,
                'top_expires_at' => null,
                'views' => 410,
                'unique_views' => 295,
                'favorites_count' => 12,
                'published_at' => Carbon::now()->subDays(20),
                'expired_at' => Carbon::now()->addDays(270),
                'created_at' => Carbon::now()->subDays(20),
                'updated_at' => Carbon::now(),
                'amenities' => ['security', 'parking']
            ],
            // Property 5: Miami Beachfront Penthouse
            [
                'uuid' => Str::uuid(),
                'user_id' => $agentId,
                'project_id' => null,
                'category_id' => $apartmentId,
                'slug' => 'beachfront-penthouse-miami',
                'price' => 8900000,
                'price_per_m2' => 44500,
                'is_negotiable' => false,
                'area' => 200,
                'land_area' => null,
                'built_area' => 200,
                'bedrooms' => 3,
                'bathrooms' => 3,
                'floors' => 1,
                'garages' => 2,
                'year_built' => 2022,
                'furnishing' => 'fully-furnished',
                'legal_status' => 'clear_title',
                'ownership_type' => 'condo',
                'country_id' => $usaId,
                'province_id' => $floridaId,
                'district_id' => $miamiDadeId,
                'ward_id' => $miamiId,
                'street' => 'Collins Avenue',
                'street_number' => '1001',
                'full_address' => '1001 Collins Avenue, Miami Beach, FL 33139',
                'latitude' => 25.7907,
                'longitude' => -80.1300,
                'status' => 'available',
                'type' => 'sale',
                'is_featured' => true,
                'is_vip' => true,
                'vip_expires_at' => Carbon::now()->addDays(45),
                'is_top' => true,
                'top_expires_at' => Carbon::now()->addDays(14),
                'views' => 2560,
                'unique_views' => 1920,
                'favorites_count' => 142,
                'published_at' => Carbon::now()->subDays(3),
                'expired_at' => Carbon::now()->addDays(180),
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now(),
                'amenities' => ['swimming-pool', 'gym', 'concierge', 'security', 'elevator', 'parking', 'balcony', 'sauna']
            ],
        ];
        
        // Insert properties
        foreach ($properties as $propertyData) {
            $amenities = $propertyData['amenities'];
            unset($propertyData['amenities']);
            
            $propertyId = DB::table('properties')->insertGetId($propertyData);
            
            // Insert translations
            $this->insertTranslations($propertyId, $propertyData['slug']);
            
            // Insert amenities
            $this->insertPropertyAmenities($propertyId, $amenities);
            
        }
    }
    
    private function insertTranslations(int $propertyId, string $slug): void
    {
        $translations = $this->getTranslations($slug);
        
        foreach ($translations as $locale => $data) {
            DB::table('property_translations')->insert([
                'property_id' => $propertyId,
                'locale' => $locale,
                'title' => $data['title'],
                'description' => $data['description']
            ]);
        }
    }
    
    private function getTranslations(string $slug): array
    {
        $translations = [
            'luxury-villa-beverly-hills' => [
                'en' => [
                    'title' => 'Luxury Villa in Beverly Hills',
                    'description' => 'Stunning luxury villa with panoramic views of Los Angeles. Features 6 bedrooms, 8 bathrooms, pool, tennis court, and 4-car garage.'
                ],
                'vi' => [
                    'title' => 'Biệt thự sang trọng tại Beverly Hills',
                    'description' => 'Biệt thự sang trọng với view toàn cảnh Los Angeles. Có 6 phòng ngủ, 8 phòng tắm, hồ bơi, sân tennis và gara 4 ô tô.'
                ],
            ],
            'luxury-apartment-manhattan' => [
                'en' => [
                    'title' => 'Luxury Apartment in Manhattan',
                    'description' => 'Luxury apartment with stunning views of Central Park. Located in the iconic Plaza building with world-class amenities.'
                ],
                'vi' => [
                    'title' => 'Căn hộ cao cấp tại Manhattan',
                    'description' => 'Căn hộ cao cấp với view tuyệt đẹp nhìn ra Central Park. Nằm trong tòa nhà Plaza mang tính biểu tượng với tiện ích đẳng cấp thế giới.'
                ],
            ],
            'townhouse-houston' => [
                'en' => [
                    'title' => 'Spacious Townhouse in Houston',
                    'description' => 'Beautiful townhouse in the heart of Houston. Perfect for families, close to shopping and dining. Features private garden and parking.'
                ],
                'vi' => [
                    'title' => 'Nhà phố rộng rãi tại Houston',
                    'description' => 'Nhà phố đẹp tại trung tâm Houston. Lý tưởng cho gia đình, gần khu mua sắm và nhà hàng. Có vườn riêng và chỗ đậu xe.'
                ],
            ],
            'land-irvine' => [
                'en' => [
                    'title' => 'Prime Land in Irvine',
                    'description' => 'Great investment opportunity! Prime land in Irvine, California. Located in a rapidly developing area near new commercial centers.'
                ],
                'vi' => [
                    'title' => 'Đất nền vị trí đẹp tại Irvine',
                    'description' => 'Cơ hội đầu tư tuyệt vời! Đất nền vị trí đẹp tại Irvine, California. Nằm trong khu vực đang phát triển nhanh gần các trung tâm thương mại mới.'
                ],
            ],
            'beachfront-penthouse-miami' => [
                'en' => [
                    'title' => 'Beachfront Penthouse in Miami',
                    'description' => 'Stunning oceanfront penthouse with breathtaking views of the Atlantic Ocean. World-class amenities including infinity pool and spa.'
                ],
                'vi' => [
                    'title' => 'Căn hộ Penthouse bên bãi biển Miami',
                    'description' => 'Căn hộ penthouse ven biển tuyệt đẹp với view ngoạn mục nhìn ra Đại Tây Dương. Tiện ích đẳng cấp thế giới bao gồm hồ bơi vô cực và spa.'
                ],
            ],
        ];
        
        return $translations[$slug] ?? [
            'en' => ['title' => $slug, 'description' => 'Property description'],
            'vi' => ['title' => $slug, 'description' => 'Mô tả bất động sản'],
        ];
    }
    
    private function insertPropertyAmenities(int $propertyId, array $amenitySlugs): void
    {
        foreach ($amenitySlugs as $slug) {
            $amenityId = DB::table('amenities')->where('slug', $slug)->value('id');
            
            if ($amenityId) {
                DB::table('property_amenities')->insert([
                    'property_id' => $propertyId,
                    'amenity_id' => $amenityId,
                    'value' => 'yes'
                ]);
            }
        }
    }
    
    
}