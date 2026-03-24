<?php
namespace App\Repositories;

use App\Models\Currency;
use App\Models\Language;
use App\Traits\Loggable;
use App\Traits\SetCurrency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class CoreRepository
{
    use Loggable, SetCurrency;

    protected Model $model;
    protected string|int|null $currency;
    protected ?string $language;
    protected string $updatedDate;

    /**
     * CoreRepository constructor.
     */
    public function __construct()
    {
        $this->model = app($this->getModelClass());
        $this->language = $this->setLang();
        $this->currency = $this->setCurrency();
        $this->updatedDate = request('updated_at', '2021-01-01');
    }

    abstract protected function getModelClass(): string;

    protected function model(): Model
    {
        return clone $this->model;
    }

    protected function setLang(): ?string
    {
        $lang = request('lang');

        if (empty($lang)) {
            $lang = Language::whereDefault(true)->first()?->locale;
        }

        if (empty($lang)) {
            $lang = Language::first()?->locale;
        }

        return $lang ?: 'en';
    }

    protected function setCurrency(): int|string|null
    {
        return request(
            'currency_id',
            Currency::currenciesList()->where('default', 1)->first()?->id
        );
    }

    /**
     * Apply language scope to query
     */
    protected function withTranslation(Builder $query, string $relation = 'translations'): Builder
    {
        return $query->with([$relation => function ($q) {
            $q->where('locale', $this->language);
        }]);
    }

    /**
     * Get order direction from filter
     */
    protected function getOrderDirection(array $filter, string $default = 'desc'): string
    {
        $sort = $filter['sort'] ?? $default;
        return in_array($sort, ['asc', 'desc']) ? $sort : $default;
    }

    /**
     * Get order column from filter
     */
    protected function getOrderColumn(array $filter, string $default = 'id'): string
    {
        $column = $filter['column'] ?? $default;
        
        // Check if column exists in table
        if (!in_array($column, $this->model->getFillable())) {
            $column = $default;
        }
        
        return $column;
    }

    /**
     * Get per page value
     */
    protected function getPerPage(array $filter, int $default = 10): int
    {
        return (int)($filter['perPage'] ?? $filter['per_page'] ?? $default);
    }
}