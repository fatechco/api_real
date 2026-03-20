<?php
namespace Modules\Auth\Http\Controllers;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Services\AuthByEmailService;
use Modules\Auth\Services\AuthByMobilePhoneService;

class RegisterController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        if ($request->input('phone')) {

            return (new AuthByMobilePhoneService)->authentication($request->validated());

        } else if ($request->input('email')) {

            return (new AuthByEmailService)->authentication($request->validated());

        }

        return $this->onErrorResponse([
            'code' => ResponseError::ERROR_400
        ]);
    }
}
