<?php
namespace Modules\User\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\User\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index(Request $request)
    {

        $query = Role::with('permissions');
        
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('display_name', 'like', "%{$request->search}%");
        }
        
        if ($request->type === 'core') {
            $query->where('is_core', true);
        } elseif ($request->type === 'custom') {
            $query->where('is_core', false);
        }
        
        if ($request->package) {
            $query->where('package_type', $request->package);
        }
        
        $roles = $query->get();        
        // Add users_count to each role
        foreach ($roles as $role) {
            $role->users_count = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->count();
        }
        
        // Sort
        switch ($request->sort) {
            case 'users':
                $roles = $roles->sortByDesc('users_count');
                break;
            case 'permissions':
                $roles = $roles->sortByDesc(function($role) {
                    return $role->permissions->count();
                });
                break;
            default:
                $roles = $roles->sortBy('display_name');
        }
        
        return response()->json([
            'data' => $roles->values(),
            'meta' => [
                'total' => $roles->count(),
            ]
        ]);
    }
    
    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $role->users_count = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->count();
        
        return response()->json(['data' => $role]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'package_type' => 'nullable|in:member,agent,agency',
        ]);
        
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'package_type' => $validated['package_type'] ?? null,
            'is_core' => false,
            'is_default' => false,
        ]);
        
        return response()->json(['data' => $role], 201);
    }
    
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        
        if ($role->is_core) {
            return response()->json(['message' => 'Cannot edit core role'], 403);
        }
        
        $validated = $request->validate([
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'package_type' => 'nullable|in:member,agent,agency',
        ]);
        
        $role->update($validated);
        
        return response()->json(['data' => $role]);
    }
    
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        if ($role->is_core) {
            return response()->json(['message' => 'Cannot delete core role'], 403);
        }
        
        if ($role->is_default) {
            return response()->json(['message' => 'Cannot delete default role'], 403);
        }
        
        $role->delete();
        
        return response()->json(['message' => 'Role deleted successfully']);
    }
    
    public function syncPermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $permissions = $request->permissions;
        
        $role->syncPermissions($permissions);
        
        return response()->json(['data' => $role->load('permissions')]);
    }
    
    public function stats()
    {
        $roles = Role::all();
        
        return response()->json([
            'data' => [
                'total' => $roles->count(),
                'core' => $roles->where('is_core', true)->count(),
                'custom' => $roles->where('is_core', false)->count(),
                'member' => $roles->where('package_type', 'member')->count(),
                'agent' => $roles->where('package_type', 'agent')->count(),
                'agency' => $roles->where('package_type', 'agency')->count(),
                'totalUsers' => DB::table('model_has_roles')->count(),
            ]
        ]);
    }
}