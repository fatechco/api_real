<?php

namespace Modules\User\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Modules\User\Models\User;

class UserRoleController extends Controller
{
    public function index($userId)
    {
        $user = User::findOrFail($userId);
        
        return response()->json(['data' => $user->roles]);
    }
    
    public function assign(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($request->role_id);
        
        $user->assignRole($role);
        
        return response()->json(['message' => 'Role assigned successfully']);
    }
    
    public function remove($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        
        $user->removeRole($role);
        
        return response()->json(['message' => 'Role removed successfully']);
    }
}