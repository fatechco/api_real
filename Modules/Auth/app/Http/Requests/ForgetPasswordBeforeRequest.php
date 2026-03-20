<?php
namespace Modules\Auth\Http\Requests;

use App\Http\Requests\BaseRequest;

class ForgetPasswordBeforeRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules(): array
	{
		return [
            'id'    => 'required|string',
            'phone' => 'required|numeric|exists:users,phone',
		];
	}
}
