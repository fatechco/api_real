<?php
namespace Modules\Location\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * Modules\Location\Models\WardTranslation
 *
 * @property int $id
 * @property int $ward_id
 * @property string $locale
 * @property string $title
 * @property Ward|null $ward
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereWardId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class WardTranslation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
