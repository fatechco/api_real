<?php
declare(strict_types=1);

namespace Modules\Location\Http\Requests\Region;

use App\Http\Requests\BaseRequest;

class RegionRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'active'  => 'required|boolean',
            'title'   => 'required|array',
            'title.*' => 'required|string|max:191',
        ];
    }
}
