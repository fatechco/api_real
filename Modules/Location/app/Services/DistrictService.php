<?php

namespace Modules\Location\Services;

use App\Helpers\ResponseError;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;
use Modules\Location\Models\District;
use Modules\Location\Models\Province;
use Throwable;

final class DistrictService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return District::class;
    }

    public function create(array $data): array
    {
        try {
            $province = Province::find(data_get($data, 'province_id'));

            $data['country_id'] = $province?->country_id;

            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(District $model, $data): array
    {
        try {
            $province = Province::find(data_get($data, 'province_id'));

            $data['country_id'] = $province?->country_id;
           
            $model->update($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = []): array
    {
        foreach (District::whereIn('id', is_array($ids) ? $ids : [])->get() as $model) {

            /** @var District $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function changeActive(int $id): array
    {
        try {
            $model = District::find($id);
            $model->update(['active' => !$model->active]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

}
