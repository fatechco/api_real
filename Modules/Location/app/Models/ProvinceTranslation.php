<?php
namespace Modules\Location\Models;

use Illuminate\Database\Eloquent\Model;

class ProvinceTranslation extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'name',
    ];
    
    /**
     * Relationships
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }
}