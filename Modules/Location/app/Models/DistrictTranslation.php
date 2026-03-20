<?php
namespace Modules\Location\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * Modules\Location\Models\DistrictTranslation
 *
 * @property int $id
 * @property int $district_id
 * @property string $locale
 * @property string $title
 * @property District|null $district
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereDistrictId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class DistrictTranslation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
