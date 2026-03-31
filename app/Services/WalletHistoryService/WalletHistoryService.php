<?php
declare(strict_types=1);

namespace App\Services\WalletHistoryService;

use DB;
use Log;
use Throwable;
use Modules\User\Models\User;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Services\CoreService;
use App\Models\WalletHistory;
use App\Helpers\ResponseError;

class WalletHistoryService extends CoreService
{
    protected function getModelClass(): string
    {
        return WalletHistory::class;
    }

    /**
     * @param array $data
     * @return array
     * @throws Throwable
     */
    public function create(array $data): array
    {
        if (!data_get($data, 'type') || !data_get($data, 'price') || !data_get($data, 'user')
        ) {
            Log::error('wallet history empty', [
                'type'  => data_get($data, 'type'),
                'price' => data_get($data, 'price'),
                'user'  => data_get($data, 'user'),
                'data'  => $data
            ]);
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'data' => 'empty'];
        }

        $walletHistory = DB::transaction(function () use ($data) {

            /** @var User $user */
            $user   = data_get($data, 'user');
            $type   = data_get($data, 'type', 'withdraw');
            $status = $data['status'] ?? WalletHistory::PROCESSED;

            /** @var WalletHistory $walletHistory */
            $walletHistory = $this->model()->create([
                'uuid'        => Str::uuid(),
                'wallet_uuid' => $user?->wallet?->uuid ?? data_get($user, 'wallet.uuid'),
                'type'        => $type,
                'price'       => data_get($data, 'price'),
                'note'        => data_get($data, 'note'),
                'created_by'  => data_get($data, 'created_by') ?? $user->id,
                'status'      => $status,
            ]);

            $walletId = Payment::where('tag', 'wallet')->first()?->id;

            $status = match($status) {
                WalletHistory::REJECTED => Transaction::STATUS_CANCELED,
                WalletHistory::PROCESSED => Transaction::STATUS_PROGRESS,
                default => $status
            };

            $transaction = $walletHistory->createTransaction([
                'price'                 => data_get($data, 'price'),
                'user_id'               => $user->id,
                'payment_sys_id'        => data_get($data, 'payment_sys_id', $walletId),
                'payment_trx_id'        => data_get($data, 'payment_trx_id', $user->wallet?->id),
                'note'                  => $user->wallet?->id,
                'perform_time'          => now(),
                'status'                => $status,
                'status_description'    => "Transaction for wallet #{$user->wallet?->id}"
            ]);

            $walletHistory->update(['transaction_id' => $transaction->id]);

            if ($walletHistory->type === 'withdraw') {
                $walletHistory->wallet()->decrement('price', $walletHistory->price);
            }

            if ($status === Transaction::STATUS_PAID && $walletHistory->type == 'topup') {
                $walletHistory->user->wallet()->increment('price', $walletHistory->price);
            }

            return $walletHistory;
        });

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $walletHistory];
    }

    /**
     * @param string $uuid
     * @param string|null $status
     * @return array
     */
    public function changeStatus(string $uuid, string $status = null): array
    {
        /** @var WalletHistory $walletHistory */
        $walletHistory = $this->model()->with('user.wallet', 'transaction')->firstWhere('uuid', $uuid);

        if (!$walletHistory || $walletHistory->status !== WalletHistory::PROCESSED) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        $status = in_array($status, [WalletHistory::REJECTED, WalletHistory::CANCELED]) ? Transaction::STATUS_CANCELED : $status;

        if ($status === Transaction::STATUS_PAID && $walletHistory->type == 'topup') {
            $walletHistory->user->wallet()->increment('price', $walletHistory->price);
        }

        $walletHistory->update([
            'status' => $status,
            'price' => $walletHistory->price
        ]);

        $status = match($status) {
            WalletHistory::REJECTED => Transaction::STATUS_CANCELED,
            WalletHistory::PROCESSED => Transaction::STATUS_PROGRESS,
            default => $status
        };

        $walletHistory->transaction?->update([
            'status' => $status
        ]);

        $isCancel = $status === WalletHistory::REJECTED || $status === WalletHistory::CANCELED;

        if ($isCancel && $walletHistory->type === 'withdraw') {
            $walletHistory->user->wallet()->increment('price', $walletHistory->price);
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

}
