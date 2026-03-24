<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class AmenityTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    public function amenity()
    {
        return $this->belongsTo(Amenity::class);
    }
}