<?php
namespace Modules\Location\Http\Requests\Country;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CountryRequest extends BaseRequest
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
            'code'          => 'required|string',
            'region_id'     => ['required', 'integer', Rule::exists('regions', 'id')],
            'images'        => 'array',
            'images.*'      => 'string',
            'title'         => 'required|array',
            'title.*'       => 'required|string|max:191',
        ];
    }
}
