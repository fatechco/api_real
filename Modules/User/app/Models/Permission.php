<?php

namespace Modules\User\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'module',
        'display_name',
        'description',
        'is_core',
    ];
    
    protected $casts = [
        'is_core' => 'boolean',
    ];
    
    // Scope để lấy permissions theo module
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }
    
    // Scope để lấy core permissions
    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }
}