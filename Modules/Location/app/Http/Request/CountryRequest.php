<?php
namespace Modules\Location\Http\Requests;
use App\Http\Requests\BaseRequest;

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
            'images'        => 'array',
            'images.*'      => 'string',
            'title'         => 'required|array',
            'title.*'       => 'required|string|max:191',
        ];
    }
}
