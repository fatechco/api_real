<?php
namespace Modules\User\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\User\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::query();
        
        if ($request->module) {
            $query->where('module', $request->module);
        }
        
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('display_name', 'like', "%{$request->search}%");
        }
        
        $permissions = $query->orderBy('module')->orderBy('name')->get();
        
        return response()->json(['data' => $permissions]);
    }
    
    public function groups()
    {
        $permissions = Permission::all();
        
        $groups = [
            'chat' => [
                'module' => 'chat',
                'label' => 'Chat System',
                'permissions' => []
            ],
            'listing' => [
                'module' => 'listing',
                'label' => 'Listing Management',
                'permissions' => []
            ],
            'ai' => [
                'module' => 'ai',
                'label' => 'AI Features',
                'permissions' => []
            ],
            'report' => [
                'module' => 'report',
                'label' => 'Reports & Analytics',
                'permissions' => []
            ],
            'team' => [
                'module' => 'team',
                'label' => 'Team Management',
                'permissions' => []
            ],
            'api' => [
                'module' => 'api',
                'label' => 'API Access',
                'permissions' => []
            ],
            'support' => [
                'module' => 'support',
                'label' => 'Support System',
                'permissions' => []
            ],
            'package' => [
                'module' => 'package',
                'label' => 'Package Management',
                'permissions' => []
            ],
            'user' => [
                'module' => 'user',
                'label' => 'User Management',
                'permissions' => []
            ],
            'setting' => [
                'module' => 'setting',
                'label' => 'System Settings',
                'permissions' => []
            ],
        ];
        
        foreach ($permissions as $permission) {
            if (isset($groups[$permission->module])) {
                $groups[$permission->module]['permissions'][] = $permission;
            }
        }
        
        // Remove empty groups
        $groups = array_filter($groups, function($group) {
            return !empty($group['permissions']);
        });
        
        return response()->json(['data' => array_values($groups)]);
    }
}