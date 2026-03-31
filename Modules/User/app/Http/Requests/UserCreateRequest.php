<?php
namespace Modules\User\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UserCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'email' => [
                'email',
                Rule::unique('users', 'email')->ignore(request()->route('user'), 'uuid')
            ],
          /*  'phone' => [
                'numeric',
                Rule::unique('users', 'phone')->ignore(request()->route('user'), 'uuid')
            ],*/
            'role'                            => ['string', 'exists:roles,name'],
            'lastname'                        => ['string'],
            'birthday'                        => ['date_format:Y-m-d'],
            'firstname'                       => ['required', 'string', 'min:2', 'max:100'],
            'gender'                          => ['string', Rule::in('male','female')],
            'active'                          => ['numeric', Rule::in(1,0)],
            'subscribe'                       => 'boolean',
            'notifications'                   => 'array',
            'notifications.*.notification_id' => ['required', 'int', Rule::exists('notifications', 'id')],
            'notifications.*.active'          => 'boolean',
            'password'                        => ['min:6', 'confirmed'],
            'referral'                        => 'string|exists:users,my_referral|max:255',
            'images'                          => 'array',
            'images.*'                        => 'string',
            
        ];
    }
}
