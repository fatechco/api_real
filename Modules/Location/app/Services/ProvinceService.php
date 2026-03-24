<?php

namespace Modules\Location\Services;

use App\Helpers\ResponseError;
use Modules\Location\Models\City;
use Modules\Location\Models\Country;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;
use Modules\Location\Models\Province;
use Throwable;

final class ProvinceService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Province::class;
    }

    public function create(array $data): array
    {
        try {
          
            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(Province $model, $data): array
    {
        try {
          
            $model->update($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502 ];
        }
    }

    public function delete(?array $ids = []): array
    {
        foreach (Province::whereIn('id', is_array($ids) ? $ids : [])->get() as $model) {

            /** @var Province $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function changeActive(int $id): array
    {
        try {
            $model = Province::find($id);
            $model->update(['active' => !$model->active]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => $e->getMessage(),
            ];
        }
    }

}
