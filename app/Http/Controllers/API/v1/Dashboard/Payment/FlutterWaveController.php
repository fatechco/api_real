<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Models\WalletHistory;
use App\Services\PaymentService\FlutterWaveService;
use Illuminate\Http\Request;
use Log;

class FlutterWaveController extends PaymentBaseController
{
    public function __construct(private FlutterWaveService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function paymentWebHook(Request $request): array
    {
        Log::error('all', $request->all());

        $status = $request->input('data.status') ?? $request->input('status');

        $status = match ($status) {
            'successful' => WalletHistory::PAID,
            default      => 'progress',
        };

        $token = $request->input('data.tx_ref') ?? $request->input('tx_ref') ?? $request->input('txRef');

        return $this->service->afterHook($token, $status);
    }

}
