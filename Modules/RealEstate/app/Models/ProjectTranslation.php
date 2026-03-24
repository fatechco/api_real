<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'developer_name',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}