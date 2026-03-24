<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'title',
        'description',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}