<?php

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;

class RealEstateDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $this->call([PropertyCategorySeeder::class]);
        $this->call([AmenitySeeder::class]);
         $this->call([PropertySeeder::class]);
    }
}
