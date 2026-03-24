<?php
namespace Modules\Location\Models;

use Illuminate\Database\Eloquent\Model;

class DistrictTranslation extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'name',
    ];
    
    /**
     * Relationships
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }
}