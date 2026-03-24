<?php

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyCategoryTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    public function propertyCategory()
    {
        return $this->belongsTo(PropertyCategory::class, 'property_category_id');
    }
}