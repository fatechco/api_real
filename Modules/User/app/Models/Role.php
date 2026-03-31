<?php

namespace Modules\User\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
        'package_type',
        'is_core',
        'is_default',
    ];
    
    protected $casts = [
        'is_core' => 'boolean',
        'is_default' => 'boolean',
    ];
    
    // Scope để lấy roles theo package type
    public function scopeByPackageType($query, $type)
    {
        return $query->where('package_type', $type);
    }
    
    // Scope để lấy core roles
    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }
    
    // Scope để lấy default role
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}