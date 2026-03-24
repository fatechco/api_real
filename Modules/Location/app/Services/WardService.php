<?php

namespace Modules\Location\Services;

use App\Helpers\ResponseError;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;
use Modules\Location\Models\District;
use Modules\Location\Models\Province;
use Modules\Location\Models\Ward;
use Throwable;

final class WardService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Ward::class;
    }

    public function create(array $data): array
    {
        try {
            $district = District::find(data_get($data, 'district_id'));

            $data['country_id'] = $district?->province?->country_id;
            $data['province_id'] = $district?->province_id;
            $data['district_id'] = $district?->id;

            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(Ward $model, $data): array
    {
        try {
            $district = District::find(data_get($data, 'district_id'));

            $data['country_id'] = $district?->province?->country_id;
            $data['province_id'] = $district?->province_id;
            $data['district_id'] = $district?->id;

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
        foreach (Ward::whereIn('id', is_array($ids) ? $ids : [])->get() as $model) {

            /** @var Ward $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function changeActive(int $id): array
    {
        try {
            $model = Ward::find($id);
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
