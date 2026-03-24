<?php

namespace Modules\Location\Models;
use Illuminate\Database\Eloquent\Model;

class CountryTranslation extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'name',
        'native_name',
    ];
    
    /**
     * Relationships
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}