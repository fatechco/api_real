<?php
namespace Modules\Location\Traits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Location\Models\Province;

/**
 * @property-read int|null $province_id
 * @property Province|null $province
*/
trait Provinces
{
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
