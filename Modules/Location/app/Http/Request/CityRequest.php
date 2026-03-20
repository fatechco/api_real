<?php
namespace Modules\Location\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CityRequest extends BaseRequest
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
            'country_id'    => ['required', 'integer', Rule::exists('countries', 'id')],
            'title'         => 'required|array',
            'title.*'       => 'required|string|max:191',
        ];
    }
}
