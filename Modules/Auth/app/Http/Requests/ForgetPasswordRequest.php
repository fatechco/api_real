<?php
namespace Modules\Auth\Http\Requests;

use App\Http\Requests\BaseRequest;

class ForgetPasswordRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules(): array
	{
		return [
            'phone'    => 'required|numeric',
            'id'       => [request('type') === 'firebase' ? 'required' : 'nullable'],
		];
	}
}
