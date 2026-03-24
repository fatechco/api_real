<?php
namespace Modules\Location\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class WardRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'active'        => 'required|boolean',
            'district_id'       => ['required', 'integer', Rule::exists('districts', 'id')],
            'title'         => 'required|array',
            'title.*'       => 'required|string|max:191',
        ];
    }
}
