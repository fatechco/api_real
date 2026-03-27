<?php
namespace Modules\Auth\Http\Controllers;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use Modules\Auth\Http\Requests\ForgetPasswordBeforeRequest;
use Modules\Auth\Http\Requests\PhoneVerifyRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\ProvideLoginRequest;
use Modules\Auth\Http\Requests\ReSendVerifyRequest;
use App\Http\Requests\FilterParamsRequest;

use App\Services\EmailSettingService\EmailSendService;

//use App\Services\UserServices\UserWalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Laravel\Sanctum\PersonalAccessToken;
use Lcobucci\JWT\UnencryptedToken;
use Spatie\Permission\Models\Role;
use App\Traits\ApiResponse;

use Str;
use Throwable;
use DB;
use Modules\Auth\Http\Requests\ForgetPasswordRequest;
use Modules\Auth\Services\AuthByMobilePhoneService;
use Modules\User\Http\Resources\UserResource;
use Modules\User\Models\User;
use Modules\User\Services\UserService;

class LoginController extends Controller
{
    use ApiResponse;

    /**
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if ($request->input('phone')) {
            return $this->loginByPhone($request);
        }

        if (!auth()->attempt($request->only(['email', 'password']))) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_102,
                'message' => __('errors.' . ResponseError::ERROR_102, locale: $this->language)
            ]);
        }

        /** @var User $user */
        $user  = auth()->user();
        $token = $user->createToken('api_token')->plainTextToken;

        /** @var User $user */
        $user  = auth('sanctum')->user();

        return $this->successResponse('User successfully login', [
            'token'         => $token,
            'access_token'  => $token,
            'token_type'    => 'Bearer',
            'user'          => UserResource::make($user->load([
                'roles',      
                'invite.shop:id,slug,uuid,logo_img',
                'invite.shop.translation' => fn($q) => $q->where('locale', $this->language)->select([
                    'id',
                    'title',
                    'shop_id',
                    'locale'
                ])
            ])),
        ]);
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    protected function loginByPhone($request): JsonResponse
    {
        if (!auth()->attempt($request->only('phone', 'password'))) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_102,
                'message' => __('errors.' . ResponseError::ERROR_102, locale: $this->language)
            ]);
        }

        /** @var User $user */
        $user  = auth()->user();
        $token = $user->createToken('api_token')->plainTextToken;

        /** @var User $user */
        $user  = auth('sanctum')->user();

        return $this->successResponse('User successfully login', [
            'token'        => $token,
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => UserResource::make($user->load(['roles'])),
        ]);
    }

    /**
     * Obtain the user information from Provider.
     *
     * @param $provider
     * @param ProvideLoginRequest $request
     * @return JsonResponse
     */
    public function handleProviderCallback($provider, ProvideLoginRequest $request): JsonResponse
    {
        try {
            $this->validateProvider($request->input('id'));
        } catch (Throwable $e) {
            $this->error($e);

            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_107,
                'message' => __('errors.' . ResponseError::ERROR_107, locale: $this->language)
            ]);
        }

        try {
            $result = DB::transaction(function () use ($request, $provider) {

                @[$firstname, $lastname] = explode(' ', (string)$request->input('name', ''));

                $defaultName      = Str::before($request->input('email'), '@');
                $defaultFirstName = Str::ucfirst(Str::replace('.', ' ', $defaultName));

                $user = User::where('email', $request->input('email'))->first();

                if (empty($user)) {
                    $user = User::create([
                        'email'             => $request->input('email'),
                        'email_verified_at' => now(),
                        'referral'          => $request->input('referral'),
                        'active'            => true,
                        'firstname'         => !empty($firstname) ? $firstname : $defaultFirstName,
                        'lastname'          => $lastname,
                    ]);
                }

                if ($request->input('avatar') && empty($user->img)) {
                    $user->update(['img' => $request->input('avatar')]);
                }

                $user->socialProviders()->updateOrCreate([
                    'provider'      => $provider,
                    'provider_id'   => $request->input('id'),
                ], [
                    'avatar' => $request->input('avatar')
                ]);

                if (!$user->hasAnyRole(Role::query()->pluck('name')->toArray())) {
                    $user->syncRoles('user');
                }

                (new UserService)->notificationSync($user);

                /*if (empty($user->wallet)) {
                    (new UserWalletService)->create($user);
                }*/

                $token = $user->createToken('api_token')->plainTextToken;

                return [
                    'token'         => $token,
                    'access_token'  => $token,
                    'token_type'    => 'Bearer',
                    'user'          => UserResource::make($user->load(['roles'])),
                ];
            });

            return $this->successResponse('User successfully login', [
                'token'         => data_get($result, 'token'),
                'access_token'  => data_get($result, 'access_token'),
                'token_type'    => 'Bearer',
                'user'          => data_get($result, 'user'),
            ]);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function checkPhone(FilterParamsRequest $request): JsonResponse
    {
        $user = User::select('phone')
            ->where('phone', $request->input('phone'))
            ->exists();

        return $this->successResponse('Success', [
            'exist' => !empty($request->input('phone')) && $user,
        ]);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function logout(FilterParamsRequest $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = auth('sanctum')->user();

            if (empty($user)) {
                return $this->successResponse();
            }

            $firebaseToken  = collect($user->firebase_token)
                ->reject(fn($item) => (string)$item == (string)$request->input('firebase_token') || empty($item) || (string)$item == (string)$request->input('token'))
                ->toArray();

            $user->update([
                'firebase_token' => $firebaseToken
            ]);

            try {
                $token   = str_replace('Bearer ', '', request()->header('Authorization'));

                $current = PersonalAccessToken::findToken($token);
                $current->delete();

            } catch (Throwable $e) {
                $this->error($e);
            }

        } catch (Throwable $e) {
            $this->error($e);
        }

        return $this->successResponse('User successfully logout');
    }

    /**
     * @param $idToken
     * @return UnencryptedToken|bool
     * @throws Exception
     */
    public function validateProvider($idToken): UnencryptedToken|bool
    {
        $data = Firebase::auth()->verifyIdToken($idToken);
        $tokenEmail = $data->claims()->get('email');

        if (!$data->isExpired(now()) && $tokenEmail === request('email')) {
            return true;
        }

        throw new Exception('expired');
    }

    /**
     * @param ForgetPasswordRequest $request
     * @return JsonResponse
     */
    public function forgetPassword(ForgetPasswordRequest $request): JsonResponse
    {
        return (new AuthByMobilePhoneService)->authentication($request->validated());
    }

    /**
     * @param ReSendVerifyRequest $request
     * @return JsonResponse
     */
    public function forgetPasswordEmail(ReSendVerifyRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        $token = mb_substr((string)time(), -6, 6);

        $result = (new EmailSendService)->sendEmailPasswordReset($user, $token);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        $user->update([
            'verify_token' => $token
        ]);

        return $this->successResponse('Verify code send');
    }

    /**
     * @param int $hash
     * @return JsonResponse
     */
    public function forgetPasswordVerifyEmail(int $hash): JsonResponse
    {
        $user = User::where('verify_token', $hash)->first();

        if (!$user) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::USER_NOT_FOUND, locale: $this->language)
            ]);
        }

        if (!$user->hasAnyRole(Role::query()->pluck('name')->toArray())) {
            $user->syncRoles('user');
        }

        $token = $user->createToken('api_token')->plainTextToken;

        $user->update([
            'active'       => true,
            'verify_token' => null
        ]);

        return $this->successResponse('User successfully login', [
            'token'         => $token,
            'access_token'  => $token,
            'token_type'    => 'Bearer',
            'user'          => UserResource::make($user->load(['roles'])),
        ]);
    }

    /**
     * @param ForgetPasswordBeforeRequest $request
     * @return JsonResponse
     */
    public function forgetPasswordBefore(ForgetPasswordBeforeRequest $request): JsonResponse
    {
        try {
            $this->validateProvider($request->input('id'));
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_107,
                'message' => __('errors.' . ResponseError::ERROR_107, locale: $this->language)
            ]);
        }

        return (new AuthByMobilePhoneService)->forgetPasswordBefore($request->validated());
    }

    /**
     * @param PhoneVerifyRequest $request
     * @return JsonResponse
     */
    public function forgetPasswordVerify(PhoneVerifyRequest $request): JsonResponse
    {
        try {
            $this->validateProvider($request->input('id'));
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_107,
                'message' => __('errors.' . ResponseError::ERROR_107, locale: $this->language)
            ]);
        }

        return (new AuthByMobilePhoneService)->forgetPasswordVerify($request->all());
    }

}
