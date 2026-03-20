<?php
namespace Modules\Location\Traits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Location\Models\District;

/**
 * @property-read int|null $district_id
 * @property District|null $district
*/
trait Districts
{
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
