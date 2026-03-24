<?php
namespace Modules\Location\Models;

use Illuminate\Database\Eloquent\Model;

class WardTranslation extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'name',
    ];
    
    /**
     * Relationships
     */
    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }
}