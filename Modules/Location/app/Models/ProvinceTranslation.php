<?php
namespace Modules\Location\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modules\Location\Models\ProvinceTranslation
 *
 * @property int $id
 * @property int $province_id
 * @property string $locale
 * @property string $title
 * @property Province|null $province
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereProvinceId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class ProvinceTranslation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
