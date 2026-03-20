<?php
namespace Modules\Location\Traits;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Location\Models\Country;

/**
 * @property-read int|null $country_id
 * @property Country|null $country
*/
trait Countries
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
