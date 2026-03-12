<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Auth;

use Throwable;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\Notification;
use App\Helpers\ResponseError;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Events\Mails\SendEmailVerification;
use App\Http\Requests\Auth\PhoneVerifyRequest;
use App\Http\Requests\Auth\ReSendVerifyRequest;
use App\Services\AuthService\AuthByMobilePhone;

class VerifyAuthController extends Controller
{
    use ApiResponse, Notification;

    public function verifyPhone(PhoneVerifyRequest $request): JsonResponse
    {
        return (new AuthByMobilePhone)->confirmOPTCode($request->all());
    }

    public function resendVerify(ReSendVerifyRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))
            ->whereNotNull('verify_token')
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        event((new SendEmailVerification($user)));

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
    }

    public function verifyEmail(?string $verifyToken): JsonResponse
    {
        $user = User::where('verify_token', $verifyToken)
            ->whereNull('email_verified_at')
            ->first();

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        try {
            $user->update(['email_verified_at' => now()]);

            $token = $user->createToken('api_token')->plainTextToken;

            return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
                'token'         => $token,
                'access_token'  => $token,
                'token_type'    => 'Bearer',
                'email'         => $user->email
            ]);
        } catch (Throwable) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_501]);
        }
    }
}
