<?php

namespace Modules\Auth\Services;

use Modules\User\Models\User;

class UserVerifyService
{
    /**
     * @param User $user
     * @return void
     */
    public function verifyPhone(User $user): void
    {
        $user->phone_verified_at = now();
        $user->save();
    }

    public function verifyEmail(User $user): void
    {
        $user->email_verified_at = now();
        $user->save();
    }
}
