<?php
namespace Modules\User\Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // ==================== DEFINE PERMISSIONS ====================
        $permissions = [
            // User Management
            ['name' => 'user.view', 'module' => 'user', 'display_name' => 'View Users', 'description' => 'View list of all users'],
            ['name' => 'user.create', 'module' => 'user', 'display_name' => 'Create User', 'description' => 'Create new user account'],
            ['name' => 'user.edit', 'module' => 'user', 'display_name' => 'Edit User', 'description' => 'Edit user information'],
            ['name' => 'user.delete', 'module' => 'user', 'display_name' => 'Delete User', 'description' => 'Delete user account'],
            ['name' => 'user.verify', 'module' => 'user', 'display_name' => 'Verify User', 'description' => 'Verify agent/agency accounts'],
            ['name' => 'user.impersonate', 'module' => 'user', 'display_name' => 'Impersonate User', 'description' => 'Login as another user'],
            
            // Role Management
            ['name' => 'role.view', 'module' => 'role', 'display_name' => 'View Roles', 'description' => 'View list of all roles'],
            ['name' => 'role.create', 'module' => 'role', 'display_name' => 'Create Role', 'description' => 'Create new role'],
            ['name' => 'role.edit', 'module' => 'role', 'display_name' => 'Edit Role', 'description' => 'Edit role information'],
            ['name' => 'role.delete', 'module' => 'role', 'display_name' => 'Delete Role', 'description' => 'Delete role'],
            ['name' => 'role.assign', 'module' => 'role', 'display_name' => 'Assign Role', 'description' => 'Assign role to users'],
            
            // Property Management
            ['name' => 'property.view', 'module' => 'listing', 'display_name' => 'View Properties', 'description' => 'View all property listings'],
            ['name' => 'property.view.own', 'module' => 'listing', 'display_name' => 'View Own Properties', 'description' => 'View own property listings'],
            ['name' => 'property.create', 'module' => 'listing', 'display_name' => 'Create Property', 'description' => 'Create new property listing'],
            ['name' => 'property.edit', 'module' => 'listing', 'display_name' => 'Edit Property', 'description' => 'Edit property listing'],
            ['name' => 'property.edit.own', 'module' => 'listing', 'display_name' => 'Edit Own Property', 'description' => 'Edit own property listing'],
            ['name' => 'property.delete', 'module' => 'listing', 'display_name' => 'Delete Property', 'description' => 'Delete property listing'],
            ['name' => 'property.delete.own', 'module' => 'listing', 'display_name' => 'Delete Own Property', 'description' => 'Delete own property listing'],
            ['name' => 'property.approve', 'module' => 'listing', 'display_name' => 'Approve Property', 'description' => 'Approve pending property listings'],
            ['name' => 'property.reject', 'module' => 'listing', 'display_name' => 'Reject Property', 'description' => 'Reject property listings'],
            ['name' => 'property.feature', 'module' => 'listing', 'display_name' => 'Feature Property', 'description' => 'Mark property as featured'],
            ['name' => 'property.vip', 'module' => 'listing', 'display_name' => 'VIP Property', 'description' => 'Mark property as VIP'],
            ['name' => 'property.urgent', 'module' => 'listing', 'display_name' => 'Urgent Property', 'description' => 'Mark property as urgent'],
            ['name' => 'property.top', 'module' => 'listing', 'display_name' => 'Top Property', 'description' => 'Push property to top'],
            ['name' => 'property.export', 'module' => 'listing', 'display_name' => 'Export Properties', 'description' => 'Export property listings to Excel'],
            ['name' => 'property.import', 'module' => 'listing', 'display_name' => 'Import Properties', 'description' => 'Import property listings from Excel'],
            ['name' => 'property.stats', 'module' => 'listing', 'display_name' => 'Property Statistics', 'description' => 'View property statistics'],
            
            // Category Management
            ['name' => 'category.view', 'module' => 'category', 'display_name' => 'View Categories', 'description' => 'View property categories'],
            ['name' => 'category.create', 'module' => 'category', 'display_name' => 'Create Category', 'description' => 'Create new category'],
            ['name' => 'category.edit', 'module' => 'category', 'display_name' => 'Edit Category', 'description' => 'Edit category'],
            ['name' => 'category.delete', 'module' => 'category', 'display_name' => 'Delete Category', 'description' => 'Delete category'],
            ['name' => 'category.reorder', 'module' => 'category', 'display_name' => 'Reorder Categories', 'description' => 'Reorder category display'],
            
            // Amenity Management
            ['name' => 'amenity.view', 'module' => 'amenity', 'display_name' => 'View Amenities', 'description' => 'View amenities list'],
            ['name' => 'amenity.create', 'module' => 'amenity', 'display_name' => 'Create Amenity', 'description' => 'Create new amenity'],
            ['name' => 'amenity.edit', 'module' => 'amenity', 'display_name' => 'Edit Amenity', 'description' => 'Edit amenity'],
            ['name' => 'amenity.delete', 'module' => 'amenity', 'display_name' => 'Delete Amenity', 'description' => 'Delete amenity'],
            
            // Project Management
            ['name' => 'project.view', 'module' => 'project', 'display_name' => 'View Projects', 'description' => 'View real estate projects'],
            ['name' => 'project.create', 'module' => 'project', 'display_name' => 'Create Project', 'description' => 'Create new project'],
            ['name' => 'project.edit', 'module' => 'project', 'display_name' => 'Edit Project', 'description' => 'Edit project'],
            ['name' => 'project.delete', 'module' => 'project', 'display_name' => 'Delete Project', 'description' => 'Delete project'],
            
            // Team Management (Agency only)
            ['name' => 'team.view', 'module' => 'team', 'display_name' => 'View Team', 'description' => 'View team members'],
            ['name' => 'team.create', 'module' => 'team', 'display_name' => 'Add Team Member', 'description' => 'Add member to team'],
            ['name' => 'team.edit', 'module' => 'team', 'display_name' => 'Edit Team Member', 'description' => 'Edit team member'],
            ['name' => 'team.delete', 'module' => 'team', 'display_name' => 'Remove Team Member', 'description' => 'Remove team member'],
            ['name' => 'team.assign', 'module' => 'team', 'display_name' => 'Assign Agent', 'description' => 'Assign agent to agency'],
            
            // Chat System
            ['name' => 'chat.basic', 'module' => 'chat', 'display_name' => 'Basic Chat', 'description' => 'Chat with property owners'],
            ['name' => 'chat.agent', 'module' => 'chat', 'display_name' => 'Agent Chat', 'description' => 'Chat with real estate agents'],
            ['name' => 'chat.team', 'module' => 'chat', 'display_name' => 'Team Chat', 'description' => 'Internal team chat'],
            ['name' => 'chat.24/7', 'module' => 'chat', 'display_name' => '24/7 Chat', 'description' => '24/7 support chat'],
            ['name' => 'chat.view', 'module' => 'chat', 'display_name' => 'View Chats', 'description' => 'View all chat conversations'],
            ['name' => 'chat.delete', 'module' => 'chat', 'display_name' => 'Delete Chat', 'description' => 'Delete chat messages'],
            ['name' => 'chat.block', 'module' => 'chat', 'display_name' => 'Block User', 'description' => 'Block user from chat'],
            
            // Report & Analytics
            ['name' => 'report.view', 'module' => 'report', 'display_name' => 'View Reports', 'description' => 'View basic reports'],
            ['name' => 'report.export', 'module' => 'report', 'display_name' => 'Export Reports', 'description' => 'Export reports to Excel'],
            ['name' => 'report.advanced', 'module' => 'report', 'display_name' => 'Advanced Reports', 'description' => 'View advanced analytics'],
            ['name' => 'report.predictive', 'module' => 'report', 'display_name' => 'Predictive Reports', 'description' => 'View predictive market analysis'],
            ['name' => 'report.agency', 'module' => 'report', 'display_name' => 'Agency Reports', 'description' => 'View agency performance reports'],
            
            // AI Features
            ['name' => 'ai.price', 'module' => 'ai', 'display_name' => 'AI Pricing', 'description' => 'AI-powered price suggestions'],
            ['name' => 'ai.match', 'module' => 'ai', 'display_name' => 'AI Matching', 'description' => 'AI-powered buyer-seller matching'],
            
            // Marketing Features
            ['name' => 'marketing.auto', 'module' => 'marketing', 'display_name' => 'Auto Posting', 'description' => 'Auto post to social media'],
            ['name' => 'marketing.social', 'module' => 'marketing', 'display_name' => 'Social Ads', 'description' => 'Social media advertising'],
            ['name' => 'marketing.email', 'module' => 'marketing', 'display_name' => 'Email Marketing', 'description' => 'Email marketing campaigns'],
            
            // API Access
            ['name' => 'api.basic', 'module' => 'api', 'display_name' => 'Basic API', 'description' => 'Basic API access'],
            ['name' => 'api.advanced', 'module' => 'api', 'display_name' => 'Advanced API', 'description' => 'Advanced API with webhooks'],
            
            // Support System
            ['name' => 'support.email', 'module' => 'support', 'display_name' => 'Email Support', 'description' => 'Email support tickets'],
            ['name' => 'support.priority', 'module' => 'support', 'display_name' => 'Priority Support', 'description' => 'Priority support response'],
            ['name' => 'support.dedicated', 'module' => 'support', 'display_name' => 'Dedicated Support', 'description' => 'Dedicated account manager'],
            ['name' => 'support.ticket.view', 'module' => 'support', 'display_name' => 'View Tickets', 'description' => 'View support tickets'],
            ['name' => 'support.ticket.reply', 'module' => 'support', 'display_name' => 'Reply Tickets', 'description' => 'Reply to support tickets'],
            ['name' => 'support.ticket.close', 'module' => 'support', 'display_name' => 'Close Tickets', 'description' => 'Close support tickets'],
            
            // Package Management
            ['name' => 'package.view', 'module' => 'package', 'display_name' => 'View Packages', 'description' => 'View subscription packages'],
            ['name' => 'package.create', 'module' => 'package', 'display_name' => 'Create Package', 'description' => 'Create new package'],
            ['name' => 'package.edit', 'module' => 'package', 'display_name' => 'Edit Package', 'description' => 'Edit package'],
            ['name' => 'package.delete', 'module' => 'package', 'display_name' => 'Delete Package', 'description' => 'Delete package'],
            ['name' => 'package.assign', 'module' => 'package', 'display_name' => 'Assign Package', 'description' => 'Assign package to user'],
            
            // Transaction Management
            ['name' => 'transaction.view', 'module' => 'payment', 'display_name' => 'View Transactions', 'description' => 'View payment transactions'],
            ['name' => 'transaction.refund', 'module' => 'payment', 'display_name' => 'Refund Transaction', 'description' => 'Process refunds'],
            ['name' => 'transaction.export', 'module' => 'payment', 'display_name' => 'Export Transactions', 'description' => 'Export transactions to Excel'],
            ['name' => 'subscription.view', 'module' => 'payment', 'display_name' => 'View Subscriptions', 'description' => 'View user subscriptions'],
            ['name' => 'subscription.cancel', 'module' => 'payment', 'display_name' => 'Cancel Subscription', 'description' => 'Cancel user subscription'],
            ['name' => 'subscription.extend', 'module' => 'payment', 'display_name' => 'Extend Subscription', 'description' => 'Extend subscription period'],
            
            // Notification System
            ['name' => 'notification.send', 'module' => 'notification', 'display_name' => 'Send Notification', 'description' => 'Send notifications to users'],
            ['name' => 'notification.broadcast', 'module' => 'notification', 'display_name' => 'Broadcast', 'description' => 'Broadcast to all users'],
            
            // Settings
            ['name' => 'setting.view', 'module' => 'setting', 'display_name' => 'View Settings', 'description' => 'View system settings'],
            ['name' => 'setting.edit', 'module' => 'setting', 'display_name' => 'Edit Settings', 'description' => 'Edit system settings'],
            ['name' => 'setting.backup', 'module' => 'setting', 'display_name' => 'Backup', 'description' => 'Perform system backup'],
            ['name' => 'setting.restore', 'module' => 'setting', 'display_name' => 'Restore', 'description' => 'Restore from backup'],
        ];
        
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                [
                    'guard_name' => 'web',
                    'module' => $permission['module'],
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                ]
            );
        }
        
        // ==================== CREATE ROLES ====================
        
        // Super Admin - Full system access
        $superAdmin = Role::updateOrCreate(
            ['name' => 'super_admin'],
            [
                'guard_name' => 'web',
                'display_name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                'is_core' => true,
                'is_default' => false,
            ]
        );
        $superAdmin->givePermissionTo(Permission::all());
        
        // Admin - Manage users and approve posts
        $admin = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'guard_name' => 'web',
                'display_name' => 'Administrator',
                'description' => 'Manage users, approve property listings, and view reports',
                'is_core' => true,
                'is_default' => false,
            ]
        );
        $admin->givePermissionTo([
            'user.view', 'user.create', 'user.edit', 'user.verify',
            'role.view',
            'property.view', 'property.approve', 'property.reject', 'property.feature',
            'category.view', 'category.create', 'category.edit',
            'amenity.view', 'amenity.create', 'amenity.edit',
            'project.view',
            'report.view', 'report.export',
            'transaction.view',
            'subscription.view',
        ]);
        
        // Manager - Approve posts and view reports
        $manager = Role::updateOrCreate(
            ['name' => 'manager'],
            [
                'guard_name' => 'web',
                'display_name' => 'Content Manager',
                'description' => 'Approve property listings and view basic reports',
                'is_core' => true,
                'is_default' => false,
            ]
        );
        $manager->givePermissionTo([
            'property.view', 'property.approve', 'property.reject',
            'category.view',
            'report.view',
        ]);
        
        // Member - Individual user
        $member = Role::updateOrCreate(
            ['name' => 'member'],
            [
                'guard_name' => 'web',
                'display_name' => 'Member',
                'description' => 'Individual user with basic listing capabilities',
                'is_core' => true,
                'is_default' => true,
            ]
        );
        $member->givePermissionTo([
            'property.view.own', 'property.create', 'property.edit.own', 'property.delete.own',
            'chat.basic',
            'support.email',
        ]);
        
        // Agent - Professional real estate agent
        $agent = Role::updateOrCreate(
            ['name' => 'agent'],
            [
                'guard_name' => 'web',
                'display_name' => 'Real Estate Agent',
                'description' => 'Professional real estate agent with advanced features',
                'is_core' => true,
                'is_default' => false,
            ]
        );
        $agent->givePermissionTo([
            'property.view.own', 'property.create', 'property.edit.own', 'property.delete.own',
            'property.vip', 'property.urgent', 'property.top',
            'property.stats',
            'chat.basic', 'chat.agent',
            'report.view',
            'support.email', 'support.priority',
        ]);
        
        // Agency - Real estate company
        $agency = Role::updateOrCreate(
            ['name' => 'agency'],
            [
                'guard_name' => 'web',
                'display_name' => 'Real Estate Agency',
                'description' => 'Real estate company with team management',
                'is_core' => true,
                'is_default' => false,
            ]
        );
        $agency->givePermissionTo([
            'property.view', 'property.create', 'property.edit', 'property.delete',
            'property.vip', 'property.urgent', 'property.top',
            'property.stats',
            'category.view',
            'team.view', 'team.create', 'team.edit', 'team.delete', 'team.assign',
            'chat.basic', 'chat.agent', 'chat.team',
            'report.view', 'report.export', 'report.agency',
            'api.basic',
            'support.email', 'support.priority', 'support.dedicated',
        ]);
        
        $this->command->info('Permissions and roles seeded successfully!');
        $this->command->info('Total permissions: ' . Permission::count());
        $this->command->info('Total roles: ' . Role::count());
    }
}