<?php

namespace Modules\Location\Models;

use App\Traits\Countries;
use App\Traits\Regions;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * App\Models\City
 *
 * @property int $id
 * @property int $country_id
 * @property boolean $active
 * @property District|null $district
 * @property Collection|District[] $districts
 * @property Collection|ProvinceTranslation[] $translations
 * @property ProvinceTranslation|null $translation
 * @property int|null $translations_count
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereCategoryId($value)
 * @mixin Eloquent
 */
class Province extends Model
{
    use Countries;

    public $guarded = ['id'];
    public $timestamps = false;

    public $casts = [
        'active'    => 'bool',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ProvinceTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ProvinceTranslation::class);
    }

    public function district(): HasOne
    {
        return $this->hasOne(District::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var City $query */
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
            ->when(data_get($filter, 'country_id'), fn($q, $countryId) => $q->where('country_id', $countryId))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q
                        ->where(fn($q) => $q->where('title', 'LIKE', "%$search%")->orWhere('id', $search))
                        ->select('id', 'province_id', 'locale', 'title');
                });
            });
    }
}
