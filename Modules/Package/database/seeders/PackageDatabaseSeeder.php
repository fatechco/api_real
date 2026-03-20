<?php
namespace Modules\Package\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Package\Models\Package;
use Illuminate\Support\Facades\DB;

class PackageDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing packages
        //DB::table('packages')->truncate();

        $packages = [
            // ============================================
            // GÓI MEMBER (Cá nhân)
            // ============================================
            [
                'name' => 'Member Basic',
                'type' => 'member',
                'role_name' => 'member_basic',
                'price' => 0,
                'credits_per_month' => 0,
                'max_agents' => null,
                'is_active' => true,
                'sort_order' => 1,
                'limits' => [
                    'listingsPerMonth' => 3,
                    'vipListings' => 0,
                    'teamMembers' => 0,
                    'apiCalls' => 0,
                    'storage' => 50
                ],
                'features' => [
                    ['code' => 'chat.basic', 'enabled' => true],
                    ['code' => 'support.email', 'enabled' => true]
                ]
            ],
            [
                'name' => 'Member VIP',
                'type' => 'member',
                'role_name' => 'member_vip',
                'price' => 199000,
                'credits_per_month' => 10,
                'max_agents' => null,
                'is_active' => true,
                'sort_order' => 2,
                'limits' => [
                    'listingsPerMonth' => 20,
                    'vipListings' => 2,
                    'teamMembers' => 0,
                    'apiCalls' => 100,
                    'storage' => 200
                ],
                'features' => [
                    ['code' => 'chat.basic', 'enabled' => true],
                    ['code' => 'listing.vip', 'enabled' => true],
                    ['code' => 'report.basic', 'enabled' => true],
                    ['code' => 'ai.price', 'enabled' => true],
                    ['code' => 'support.priority', 'enabled' => true]
                ]
            ],

            // ============================================
            // Packages for AGENT 
            // ============================================
            [
                'name' => 'Agent Basic',
                'type' => 'agent',
                'role_name' => 'agent_basic',
                'price' => 499000,
                'credits_per_month' => 30,
                'max_agents' => null,
                'is_active' => true,
                'sort_order' => 3,
                'limits' => [
                    'listingsPerMonth' => 50,
                    'vipListings' => 5,
                    'teamMembers' => 0,
                    'apiCalls' => 500,
                    'storage' => 500
                ],
                'features' => [
                    ['code' => 'chat.basic', 'enabled' => true],
                    ['code' => 'chat.agent', 'enabled' => true],
                    ['code' => 'listing.vip', 'enabled' => true],
                    ['code' => 'listing.urgent', 'enabled' => true],
                    ['code' => 'report.basic', 'enabled' => true],
                    ['code' => 'ai.price', 'enabled' => true],
                    ['code' => 'support.priority', 'enabled' => true]
                ]
            ],
            [
                'name' => 'Agent Pro',
                'type' => 'agent',
                'role_name' => 'agent_pro',
                'price' => 999000,
                'credits_per_month' => 80,
                'max_agents' => null,
                'is_active' => true,
                'sort_order' => 4,
                'limits' => [
                    'listingsPerMonth' => 200,
                    'vipListings' => 20,
                    'teamMembers' => 0,
                    'apiCalls' => 2000,
                    'storage' => 2000
                ],
                'features' => [
                    ['code' => 'chat.basic', 'enabled' => true],
                    ['code' => 'chat.agent', 'enabled' => true],
                    ['code' => 'listing.vip', 'enabled' => true],
                    ['code' => 'listing.urgent', 'enabled' => true],
                    ['code' => 'listing.top', 'enabled' => true],
                    ['code' => 'report.advanced', 'enabled' => true],
                    ['code' => 'ai.price', 'enabled' => true],
                    ['code' => 'ai.match', 'enabled' => true],
                    ['code' => 'marketing.social', 'enabled' => true],
                    ['code' => 'support.dedicated', 'enabled' => true]
                ]
            ],

            // ============================================
            // Packages for AGENCY
            // ============================================
            [
                'name' => 'Business Basic',
                'type' => 'agency',
                'role_name' => 'agency_basic',
                'price' => 2990000,
                'credits_per_month' => 200,
                'max_agents' => 5,
                'is_active' => true,
                'sort_order' => 5,
                'limits' => [
                    'listingsPerMonth' => 500,
                    'vipListings' => 50,
                    'teamMembers' => 5,
                    'apiCalls' => 5000,
                    'storage' => 5000
                ],
                'features' => [
                    ['code' => 'chat.basic', 'enabled' => true],
                    ['code' => 'chat.agent', 'enabled' => true],
                    ['code' => 'chat.team', 'enabled' => true],
                    ['code' => 'listing.vip', 'enabled' => true],
                    ['code' => 'listing.urgent', 'enabled' => true],
                    ['code' => 'listing.top', 'enabled' => true],
                    ['code' => 'report.advanced', 'enabled' => true],
                    ['code' => 'team.basic', 'enabled' => true],
                    ['code' => 'api.basic', 'enabled' => true],
                    ['code' => 'marketing.auto', 'enabled' => true],
                    ['code' => 'support.dedicated', 'enabled' => true]
                ]
            ],
            [
                'name' => 'Business Premium',
                'type' => 'agency',
                'role_name' => 'agency_premium',
                'price' => 9990000,
                'credits_per_month' => 1000,
                'max_agents' => 50,
                'is_active' => true,
                'sort_order' => 6,
                'limits' => [
                    'listingsPerMonth' => 2000,
                    'vipListings' => 200,
                    'teamMembers' => 50,
                    'apiCalls' => 50000,
                    'storage' => 20000
                ],
                'features' => [
                    ['code' => 'chat.basic', 'enabled' => true],
                    ['code' => 'chat.agent', 'enabled' => true],
                    ['code' => 'chat.team', 'enabled' => true],
                    ['code' => 'chat.24/7', 'enabled' => true],
                    ['code' => 'listing.vip', 'enabled' => true],
                    ['code' => 'listing.urgent', 'enabled' => true],
                    ['code' => 'listing.top', 'enabled' => true],
                    ['code' => 'report.basic', 'enabled' => true],
                    ['code' => 'report.advanced', 'enabled' => true],
                    ['code' => 'report.predictive', 'enabled' => true],
                    ['code' => 'team.basic', 'enabled' => true],
                    ['code' => 'team.pro', 'enabled' => true],
                    ['code' => 'team.enterprise', 'enabled' => true],
                    ['code' => 'api.basic', 'enabled' => true],
                    ['code' => 'api.advanced', 'enabled' => true],
                    ['code' => 'marketing.auto', 'enabled' => true],
                    ['code' => 'marketing.social', 'enabled' => true],
                    ['code' => 'marketing.email', 'enabled' => true],
                    ['code' => 'ai.price', 'enabled' => true],
                    ['code' => 'ai.match', 'enabled' => true],
                    ['code' => 'support.dedicated', 'enabled' => true]
                ]
            ]
        ];

        // Insert packages
        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['role_name' => $package['role_name']],
                $package
            );
        }

        $this->command->info('Packages seeded successfully!');
        $this->command->info('Total packages: ' . count($packages));
    }
}