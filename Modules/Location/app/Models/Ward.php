<?php
namespace Modules\Location\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * Modules\Location\Models\Ward
 *
 * @property int $id
 * @property boolean $active
 * @property int|null $country_id
 * @property int|null $province_id
 * @property int|null $district_id
 * @property Collection|WardTranslation[] $translations
 * @property WardTranslation|null $translation
 * @property int|null $translations_count

 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class Ward extends Model
{
    use Countries, Provinces , Districts;

    public $guarded     = ['id'];
    public $timestamps  = false;

    public $casts = [
        'active' => 'bool',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(WardTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(DistrictTranslation::class);
    }


    public function scopeActive($query): Builder
    {
        /** @var Area $query */
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter): void
    {
        $query
            ->when(request()->is('api/v1/rest/*') && request('lang'), function ($q) {
                $q->whereHas('translation', fn($query) => $query->where(function ($q) {

                    

                    $q->where('locale', request('lang'));

                }));
            })
            ->when(data_get($filter, 'country_id'), fn($q, $countryId)  => $q->where('country_id',  $countryId))
            ->when(data_get($filter, 'province_id'), fn($q, $provinceId) => $q->where('province_id', $provinceId))
            ->when(data_get($filter, 'district_id'), fn($q, $districtId) => $q->where('district_id', $districtId))
            ->when(isset($filter['active']),            fn($q)              => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q
                        ->where(fn($q) => $q->where('title', 'LIKE', "%$search%")->orWhere('id', $search))
                        ->select('id', 'ward_id', 'locale', 'title');
                });
            });
    }
}
