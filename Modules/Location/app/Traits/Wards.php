<?php
namespace Modules\Location\Traits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Location\Models\Ward;

/**
 * @property-read int|null $ward_id
 * @property Ward|null $ward
*/
trait Wards
{
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
