<?php
namespace Modules\User\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Modules\User\Models\Permission;
use Illuminate\Http\Request;
use Modules\User\Models\User;

class UserPermissionController extends Controller
{
    /**
     * Get all permissions of a specific user
     * 
     * GET /api/v1/admin/users/{user}/permissions
     * 
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($userId)
    {
        $user = User::findOrFail($userId);
        
        // Get all permissions (from roles + direct)
        $allPermissions = $user->getAllPermissions();
        
        // Get direct permissions (not from roles)
        $directPermissions = $user->getDirectPermissions();
        
        // Get permissions via roles
        $rolePermissions = $user->getPermissionsViaRoles();
        
        // Group permissions by source
        $grouped = [
            'direct' => $directPermissions->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'description' => $permission->description,
                    'module' => $permission->module,
                ];
            }),
            'via_roles' => $rolePermissions->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'description' => $permission->description,
                    'module' => $permission->module,
                ];
            }),
            'all' => $allPermissions->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'description' => $permission->description,
                    'module' => $permission->module,
                ];
            }),
        ];
        
        // Get user's roles for context
        $userRoles = $user->roles->map(function($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'roles' => $userRoles,
                'permissions' => $grouped,
            ]
        ]);
    }
    
    /**
     * Give a permission to a user
     * 
     * POST /api/v1/admin/users/{user}/permissions
     * Body: { "permission": "permission.name" }
     * 
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function give(Request $request, $userId)
    {
        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);
        
        $user = User::findOrFail($userId);
        $permission = Permission::findByName($validated['permission']);
        
        // Check if user already has this permission directly
        if ($user->hasDirectPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'User already has this permission',
            ], 400);
        }
        
        try {
            $user->givePermissionTo($permission);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission granted successfully',
                'data' => [
                    'user_id' => $user->id,
                    'permission' => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to grant permission: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Revoke a permission from a user
     * 
     * DELETE /api/v1/admin/users/{user}/permissions/{permission}
     * 
     * @param int $userId
     * @param string $permissionName
     * @return \Illuminate\Http\JsonResponse
     */
    public function revoke($userId, $permissionName)
    {
        $user = User::findOrFail($userId);
        
        // Check if permission exists
        try {
            $permission = Permission::findByName($permissionName);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }
        
        // Check if user has this permission
        if (!$user->hasDirectPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have this permission',
            ], 400);
        }
        
        try {
            $user->revokePermissionTo($permission);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission revoked successfully',
                'data' => [
                    'user_id' => $user->id,
                    'permission' => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke permission: ' . $e->getMessage(),
            ], 500);
        }
    }
}