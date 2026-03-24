<?php

namespace Modules\Location\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class USALocationSeeder extends Seeder
{
    public function run(): void
    {
        // Create USA country
        $usaId = DB::table('countries')->insertGetId([
            'code' => 'US',
            'phone_code' => '+1',
            'active' => true,
            'is_default' => false,
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Country translations
        DB::table('country_translations')->insert([
            [
                'country_id' => $usaId,
                'locale' => 'en',
                'name' => 'United States',
                'native_name' => 'United States of America',

            ],
            [
                'country_id' => $usaId,
                'locale' => 'vi',
                'name' => 'Hoa Kỳ',
                'native_name' => 'Hợp chúng quốc Hoa Kỳ',
            ],
            [
                'country_id' => $usaId,
                'locale' => 'fr',
                'name' => 'États-Unis',
                'native_name' => 'États-Unis d\'Amérique',
            ],
        ]);
        
        // Create states (provinces)
        $states = [
            ['code' => 'CA', 'name_en' => 'California', 'name_vi' => 'California', 'type' => 'state'],
            ['code' => 'NY', 'name_en' => 'New York', 'name_vi' => 'New York', 'type' => 'state'],
            ['code' => 'TX', 'name_en' => 'Texas', 'name_vi' => 'Texas', 'type' => 'state'],
            ['code' => 'FL', 'name_en' => 'Florida', 'name_vi' => 'Florida', 'type' => 'state'],
            ['code' => 'IL', 'name_en' => 'Illinois', 'name_vi' => 'Illinois', 'type' => 'state'],
            ['code' => 'PA', 'name_en' => 'Pennsylvania', 'name_vi' => 'Pennsylvania', 'type' => 'state'],
            ['code' => 'OH', 'name_en' => 'Ohio', 'name_vi' => 'Ohio', 'type' => 'state'],
            ['code' => 'GA', 'name_en' => 'Georgia', 'name_vi' => 'Georgia', 'type' => 'state'],
            ['code' => 'NC', 'name_en' => 'North Carolina', 'name_vi' => 'Bắc Carolina', 'type' => 'state'],
            ['code' => 'MI', 'name_en' => 'Michigan', 'name_vi' => 'Michigan', 'type' => 'state'],
        ];
        
        foreach ($states as $state) {
            $stateId = DB::table('provinces')->insertGetId([
                'country_id' => $usaId,
                'code' => $state['code'],
                'type' => $state['type'],
                'active' => true,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::table('province_translations')->insert([
                [
                    'province_id' => $stateId,
                    'locale' => 'en',
                    'name' => $state['name_en'],
                ],
                [
                    'province_id' => $stateId,
                    'locale' => 'vi',
                    'name' => $state['name_vi'],
                ],
            ]);
        }
        
        // Create districts for California
        $this->createCaliforniaDistricts($usaId);
        
        // Create districts for New York
        $this->createNewYorkDistricts($usaId);
        
        // Create districts for Texas
        $this->createTexasDistricts($usaId);
        
        // Create districts for Florida
        $this->createFloridaDistricts($usaId);
    }
    
    private function createCaliforniaDistricts($countryId): void
    {
        $californiaId = DB::table('provinces')->where('code', 'CA')->where('country_id', $countryId)->value('id');
        
        $districts = [
            ['code' => 'LA', 'name_en' => 'Los Angeles County', 'name_vi' => 'Quận Los Angeles', 'type' => 'county'],
            ['code' => 'ORA', 'name_en' => 'Orange County', 'name_vi' => 'Quận Cam', 'type' => 'county'],
            ['code' => 'SD', 'name_en' => 'San Diego County', 'name_vi' => 'Quận San Diego', 'type' => 'county'],
            ['code' => 'SF', 'name_en' => 'San Francisco County', 'name_vi' => 'Quận San Francisco', 'type' => 'county'],
            ['code' => 'SJ', 'name_en' => 'Santa Clara County', 'name_vi' => 'Quận Santa Clara', 'type' => 'county'],
            ['code' => 'ALA', 'name_en' => 'Alameda County', 'name_vi' => 'Quận Alameda', 'type' => 'county'],
            ['code' => 'SAC', 'name_en' => 'Sacramento County', 'name_vi' => 'Quận Sacramento', 'type' => 'county'],
        ];
        
        foreach ($districts as $district) {
            $districtId = DB::table('districts')->insertGetId([
                'province_id' => $californiaId,
                'country_id' => $countryId,
                'code' => $district['code'],
                'type' => $district['type'],
                'active' => true,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::table('district_translations')->insert([
                [
                    'district_id' => $districtId,
                    'locale' => 'en',
                    'name' => $district['name_en']
                ],
                [
                    'district_id' => $districtId,
                    'locale' => 'vi',
                    'name' => $district['name_vi']
                ],
            ]);
            
            // Create wards/cities for each district
            $this->createCaliforniaCities($districtId, $district['code'], $countryId);
        }
    }
    
    private function createCaliforniaCities($districtId, $districtCode, $countryId): void
    {
        $cities = [];
        
        switch ($districtCode) {
            case 'LA':
                $cities = [
                    ['code' => 'LAX', 'name_en' => 'Los Angeles', 'name_vi' => 'Los Angeles', 'type' => 'city'],
                    ['code' => 'BUR', 'name_en' => 'Burbank', 'name_vi' => 'Burbank', 'type' => 'city'],
                    ['code' => 'PAS', 'name_en' => 'Pasadena', 'name_vi' => 'Pasadena', 'type' => 'city'],
                    ['code' => 'LGB', 'name_en' => 'Long Beach', 'name_vi' => 'Long Beach', 'type' => 'city'],
                    ['code' => 'SM', 'name_en' => 'Santa Monica', 'name_vi' => 'Santa Monica', 'type' => 'city'],
                ];
                break;
            case 'ORA':
                $cities = [
                    ['code' => 'IRV', 'name_en' => 'Irvine', 'name_vi' => 'Irvine', 'type' => 'city'],
                    ['code' => 'ANA', 'name_en' => 'Anaheim', 'name_vi' => 'Anaheim', 'type' => 'city'],
                    ['code' => 'SANTA', 'name_en' => 'Santa Ana', 'name_vi' => 'Santa Ana', 'type' => 'city'],
                    ['code' => 'HUNT', 'name_en' => 'Huntington Beach', 'name_vi' => 'Huntington Beach', 'type' => 'city'],
                ];
                break;
            case 'SD':
                $cities = [
                    ['code' => 'SD', 'name_en' => 'San Diego', 'name_vi' => 'San Diego', 'type' => 'city'],
                    ['code' => 'CHU', 'name_en' => 'Chula Vista', 'name_vi' => 'Chula Vista', 'type' => 'city'],
                    ['code' => 'OCE', 'name_en' => 'Oceanside', 'name_vi' => 'Oceanside', 'type' => 'city'],
                ];
                break;
            case 'SF':
                $cities = [
                    ['code' => 'SF', 'name_en' => 'San Francisco', 'name_vi' => 'San Francisco', 'type' => 'city'],
                    ['code' => 'DALY', 'name_en' => 'Daly City', 'name_vi' => 'Daly City', 'type' => 'city'],
                ];
                break;
            case 'SJ':
                $cities = [
                    ['code' => 'SJ', 'name_en' => 'San Jose', 'name_vi' => 'San Jose', 'type' => 'city'],
                    ['code' => 'SUN', 'name_en' => 'Sunnyvale', 'name_vi' => 'Sunnyvale', 'type' => 'city'],
                    ['code' => 'PALO', 'name_en' => 'Palo Alto', 'name_vi' => 'Palo Alto', 'type' => 'city'],
                    ['code' => 'MOU', 'name_en' => 'Mountain View', 'name_vi' => 'Mountain View', 'type' => 'city'],
                ];
                break;
            case 'ALA':
                $cities = [
                    ['code' => 'OAK', 'name_en' => 'Oakland', 'name_vi' => 'Oakland', 'type' => 'city'],
                    ['code' => 'BERK', 'name_en' => 'Berkeley', 'name_vi' => 'Berkeley', 'type' => 'city'],
                    ['code' => 'FREM', 'name_en' => 'Fremont', 'name_vi' => 'Fremont', 'type' => 'city'],
                ];
                break;
            case 'SAC':
                $cities = [
                    ['code' => 'SAC', 'name_en' => 'Sacramento', 'name_vi' => 'Sacramento', 'type' => 'city'],
                    ['code' => 'ELK', 'name_en' => 'Elk Grove', 'name_vi' => 'Elk Grove', 'type' => 'city'],
                ];
                break;
        }
        
        foreach ($cities as $city) {
            $cityId = DB::table('wards')->insertGetId([
                'district_id' => $districtId,
                'province_id' => DB::table('provinces')->where('code', 'CA')->value('id'),
                'country_id' => $countryId,
                'code' => $city['code'],
                'type' => $city['type'],
                'active' => true,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::table('ward_translations')->insert([
                [
                    'ward_id' => $cityId,
                    'locale' => 'en',
                    'name' => $city['name_en']
                ],
                [
                    'ward_id' => $cityId,
                    'locale' => 'vi',
                    'name' => $city['name_vi']
                ],
            ]);
        }
    }
    
    private function createNewYorkDistricts($countryId): void
    {
        $newYorkId = DB::table('provinces')->where('code', 'NY')->where('country_id', $countryId)->value('id');
        
        $districts = [
            ['code' => 'MAN', 'name_en' => 'Manhattan', 'name_vi' => 'Manhattan', 'type' => 'borough'],
            ['code' => 'BRK', 'name_en' => 'Brooklyn', 'name_vi' => 'Brooklyn', 'type' => 'borough'],
            ['code' => 'QUE', 'name_en' => 'Queens', 'name_vi' => 'Queens', 'type' => 'borough'],
            ['code' => 'BRX', 'name_en' => 'The Bronx', 'name_vi' => 'The Bronx', 'type' => 'borough'],
            ['code' => 'SI', 'name_en' => 'Staten Island', 'name_vi' => 'Staten Island', 'type' => 'borough'],
        ];
        
        foreach ($districts as $district) {
            $districtId = DB::table('districts')->insertGetId([
                'province_id' => $newYorkId,
                'country_id' => $countryId,
                'code' => $district['code'],
                'type' => $district['type'],
                'active' => true,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::table('district_translations')->insert([
                [
                    'district_id' => $districtId,
                    'locale' => 'en',
                    'name' => $district['name_en'],
                ],
                [
                    'district_id' => $districtId,
                    'locale' => 'vi',
                    'name' => $district['name_vi'],
                ],
            ]);
        }
    }
    
    private function createTexasDistricts($countryId): void
    {
        $texasId = DB::table('provinces')->where('code', 'TX')->where('country_id', $countryId)->value('id');
        
        $districts = [
            ['code' => 'HAR', 'name_en' => 'Harris County', 'name_vi' => 'Quận Harris', 'type' => 'county'],
            ['code' => 'DAL', 'name_en' => 'Dallas County', 'name_vi' => 'Quận Dallas', 'type' => 'county'],
            ['code' => 'TRA', 'name_en' => 'Travis County', 'name_vi' => 'Quận Travis', 'type' => 'county'],
            ['code' => 'BEX', 'name_en' => 'Bexar County', 'name_vi' => 'Quận Bexar', 'type' => 'county'],
            ['code' => 'TAR', 'name_en' => 'Tarrant County', 'name_vi' => 'Quận Tarrant', 'type' => 'county'],
        ];
        
        foreach ($districts as $district) {
            $districtId = DB::table('districts')->insertGetId([
                'province_id' => $texasId,
                'country_id' => $countryId,
                'code' => $district['code'],
                'type' => $district['type'],
                'active' => true,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::table('district_translations')->insert([
                [
                    'district_id' => $districtId,
                    'locale' => 'en',
                    'name' => $district['name_en'],
                  
                ],
                [
                    'district_id' => $districtId,
                    'locale' => 'vi',
                    'name' => $district['name_vi'],
        
                ],
            ]);
            
            // Create cities for Harris County (Houston)
            if ($district['code'] === 'HAR') {
                $cities = [
                    ['code' => 'HOU', 'name_en' => 'Houston', 'name_vi' => 'Houston', 'type' => 'city'],
                    ['code' => 'PAS', 'name_en' => 'Pasadena', 'name_vi' => 'Pasadena', 'type' => 'city'],
                ];
                
                foreach ($cities as $city) {
                    DB::table('wards')->insert([
                        'district_id' => $districtId,
                        'province_id' => $texasId,
                        'country_id' => $countryId,
                        'code' => $city['code'],
                        'type' => $city['type'],
                        'active' => true,
                        'order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    DB::table('ward_translations')->insert([
                        [
                            'ward_id' => DB::getPdo()->lastInsertId(),
                            'locale' => 'en',
                            'name' => $city['name_en'],
                          
                        ],
                        [
                            'ward_id' => DB::getPdo()->lastInsertId(),
                            'locale' => 'vi',
                            'name' => $city['name_vi'],
                          
                        ],
                    ]);
                }
            }
        }
    }
    
    private function createFloridaDistricts($countryId): void
    {
        $floridaId = DB::table('provinces')->where('code', 'FL')->where('country_id', $countryId)->value('id');
        
        $districts = [
            ['code' => 'MIA', 'name_en' => 'Miami-Dade County', 'name_vi' => 'Quận Miami-Dade', 'type' => 'county'],
            ['code' => 'BRO', 'name_en' => 'Broward County', 'name_vi' => 'Quận Broward', 'type' => 'county'],
            ['code' => 'PAL', 'name_en' => 'Palm Beach County', 'name_vi' => 'Quận Palm Beach', 'type' => 'county'],
            ['code' => 'ORA', 'name_en' => 'Orange County', 'name_vi' => 'Quận Cam', 'type' => 'county'],
            ['code' => 'HIL', 'name_en' => 'Hillsborough County', 'name_vi' => 'Quận Hillsborough', 'type' => 'county'],
        ];
        
        foreach ($districts as $district) {
            $districtId = DB::table('districts')->insertGetId([
                'province_id' => $floridaId,
                'country_id' => $countryId,
                'code' => $district['code'],
                'type' => $district['type'],
                'active' => true,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::table('district_translations')->insert([
                [
                    'district_id' => $districtId,
                    'locale' => 'en',
                    'name' => $district['name_en'],

                ],
                [
                    'district_id' => $districtId,
                    'locale' => 'vi',
                    'name' => $district['name_vi'],
    
                ],
            ]);
            
            // Create cities for Miami-Dade County
            if ($district['code'] === 'MIA') {
                $cities = [
                    ['code' => 'MIA', 'name_en' => 'Miami', 'name_vi' => 'Miami', 'type' => 'city'],
                    ['code' => 'MIA_BEACH', 'name_en' => 'Miami Beach', 'name_vi' => 'Miami Beach', 'type' => 'city'],
                    ['code' => 'HIAL', 'name_en' => 'Hialeah', 'name_vi' => 'Hialeah', 'type' => 'city'],
                ];
                
                foreach ($cities as $city) {
                    DB::table('wards')->insert([
                        'district_id' => $districtId,
                        'province_id' => $floridaId,
                        'country_id' => $countryId,
                        'code' => $city['code'],
                        'type' => $city['type'],
                        'active' => true,
                        'order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    DB::table('ward_translations')->insert([
                        [
                            'ward_id' => DB::getPdo()->lastInsertId(),
                            'locale' => 'en',
                            'name' => $city['name_en'],
                           
                        ],
                        [
                            'ward_id' => DB::getPdo()->lastInsertId(),
                            'locale' => 'vi',
                            'name' => $city['name_vi'],
                          
                        ],
                    ]);
                }
            }
        }
    }
}