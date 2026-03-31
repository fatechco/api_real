<?php

namespace Modules\User\Services;

use App\Helpers\ResponseError;
use App\Http\Resources\UserResource;
use App\Models\Invitation;
use App\Models\Notification;
use App\Models\PushNotification;
use Modules\User\Models\User;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Exception;
use Throwable;

class UserService extends CoreService
{
    use SetTranslations, \App\Traits\Notification;

    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * Create a new user
     */
    public function create(array $data): array
    {
        try {
            $data['password'] = bcrypt($data['password'] ?? 'password');

            if (isset($data['phone'])) {
                $data['phone'] = preg_replace('/\D/', '', (string)$data['phone']);
            }

            /** @var User $user */
            $user = $this->model()->create($data + ['ip_address' => request()->ip()]);

            if (!$user) {
                return [
                    'status' => false,
                    'code'   => ResponseError::ERROR_400,
                    'message' => 'Failed to create user'
                ];
            }

            // Assign role
            $role = $data['role'] ?? 'member';
            $user->syncRoles([$role]);

            // Load roles relationship
            $user->load('roles');

            $this->notificationSync($user);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $user
            ];
        } catch (Throwable $e) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update user
     */
    public function update(string $uuid, array $data): array
    {
        /** @var User $auth */
        $auth = auth('sanctum')->user();

        $user = $this->model()
            ->where('uuid', $uuid)
            ->when(
                !$auth->hasRole('admin')
                && $auth->hasRole('seller')
                && isset($data['shop_id']),
                function ($query) use ($data) {
                    $query->whereHas('invitations', fn($q) => $q->whereIn('shop_id', (array)$data['shop_id']));
                }
            )
            ->first();

        if (!$user) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {
            DB::transaction(function () use ($user, $data) {

                if (isset($data['password']) && !empty($data['password'])) {
                    $data['password'] = bcrypt($data['password']);
                } else {
                    unset($data['password']);
                }

                if (isset($data['firebase_token'])) {
                    $token = (array)$user->firebase_token;
                    $data['firebase_token'] = array_push($token, $data['firebase_token']);
                }

                if (isset($data['phone'])) {
                    $data['phone'] = preg_replace('/\D/', '', (string)($data['phone']));
                }

                $user->update($data);

                $this->setTranslations($user, $data);

                if (isset($data['subscribe'])) {
                    $user->emailSubscription()->updateOrCreate([
                        'user_id' => $user->id
                    ], [
                        'active' => !!$data['subscribe']
                    ]);
                }

                if (isset($data['notifications'])) {
                    $user->notifications()->sync($data['notifications']);
                }

                if (isset($data['images'][0])) {
                    $user->galleries()->delete();
                    $user->update(['img' => $data['images'][0]]);
                    $user->uploads($data['images']);
                }

                if (isset($data['role'])) {
                    $user->syncRoles([$data['role']]);

                    if (in_array($data['role'], ['moderator', 'deliveryman', 'master']) && isset($data['shop_id'])) {
                        if (isset($data['delete_shop_id'])) {
                            $user->invitations()->whereIn('shop_id', $data['delete_shop_id'])->delete();
                        }

                        foreach ($data['shop_id'] as $shopId) {
                            $user->invitations()->updateOrCreate([
                                'shop_id'    => $shopId,
                                'created_by' => $user->id,
                            ], [
                                'role'       => $data['role'],
                                'status'     => Invitation::ACCEPTED,
                            ]);
                        }
                    }
                }
            });

            // Load relationships after transaction
            $user->load(['emailSubscription', 'notifications', 'invitations', 'roles']);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $user
            ];
        } catch (Throwable $e) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update user password
     */
    public function updatePassword($uuid, $password): array
    {
        $user = $this->model()->firstWhere('uuid', $uuid);

        if (!$user) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {
            $user->update(['password' => bcrypt($password)]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $user
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Login as user (impersonate)
     */
    public function loginAsUser($uuid): array
    {
        $user = $this->model()->firstWhere('uuid', $uuid);

        if (!$user) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {
            /** @var User $user */
            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => [
                    'access_token'  => $user->createToken('api_token')->plainTextToken,
                    'token_type'    => 'Bearer',
                    'user'          => new UserResource($user),
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update notifications
     */
    public function updateNotifications(array $data): array
    {
        try {
            /** @var User $user */
            $user = auth('sanctum')->user();

            DB::table('notification_user')->where('user_id', $user->id)->delete();

            $user->notifications()->attach(data_get($data, 'notifications'));

            $user->load('notifications');

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $user
            ];
        } catch (Exception $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => 'cant update notifications'
            ];
        }
    }

    /**
     * Update currency
     */
    public function updateCurrency(int $currencyId): array
    {
        try {
            /** @var User $user */
            $user = auth('sanctum')->user();

            $user->update(['currency_id' => $currencyId]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $user
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update language
     */
    public function updateLang(string $lang): array
    {
        try {
            /** @var User $user */
            $user = auth('sanctum')->user();

            $user->update(['lang' => $lang]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $user
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete users
     */
    public function delete(?array $ids = []): array
    {
        try {
            User::whereIn('id', $ids)->delete();

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update Firebase token
     */
    public function firebaseTokenUpdate(?string $firebaseToken): array
    {
        if (empty($firebaseToken)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => 'token is empty'
            ];
        }

        /** @var User $user */
        $user = auth('sanctum')->user();

        $tokens   = is_array($user->firebase_token) ? $user->firebase_token : [$user->firebase_token];
        $tokens[] = $firebaseToken;

        $user->update(['firebase_token' => array_values(array_unique($tokens))]);

        return ['status' => true];
    }

    /**
     * Toggle user active status
     */
    public function setActive(User $user): void
    {
        $user->update(['active' => !$user->active]);
    }

    /**
     * Create default working days for user
     */
    public function createDefaultWorkingDays(User $user): void
    {
        $user->workingDays()->createMany([
            ['day' => 'monday', 'from' => '09:00', 'to' => '18:00', 'disabled' => false],
            ['day' => 'tuesday', 'from' => '09:00', 'to' => '18:00', 'disabled' => false],
            ['day' => 'wednesday', 'from' => '09:00', 'to' => '18:00', 'disabled' => false],
            ['day' => 'thursday', 'from' => '09:00', 'to' => '18:00', 'disabled' => false],
            ['day' => 'friday', 'from' => '09:00', 'to' => '18:00', 'disabled' => false],
            ['day' => 'saturday', 'from' => '09:00', 'to' => '18:00', 'disabled' => false],
            ['day' => 'sunday', 'from' => '09:00', 'to' => '18:00', 'disabled' => false]
        ]);
    }

    /**
     * Sync user notifications
     */
    protected function notificationSync(User $user): void
    {
        $id = Notification::where('type', Notification::PUSH)
            ->select(['id', 'type'])
            ->first()
            ?->id;

        if ($id) {
            $user->notifications()->sync([$id]);
        } else {
            $user->notifications()->delete();
        }
    }
}