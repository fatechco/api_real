<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use Log;
use Http;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\PaymentService\MercadoPagoService;

class MercadoPagoController extends PaymentBaseController
{
    public function __construct(private MercadoPagoService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function paymentWebHook(Request $request): array
    {
        Log::error('mercado pago', $request->all());

        $id = $request->input('id');

        if (!$id) {
            return ['message' => 'empty', 'status' => false];
        }

        $payment = Payment::where('tag',Payment::TAG_MERCADO_PAGO)->first();
        $payload = $payment->paymentPayload?->payload;

        $headers = [
            'Authorization' => 'Bearer '. data_get($payload,'token')
        ];

        $url = $request->input('resource');

        if (empty($url)) {
            return ['message' => 'status not 200,2001', 'status' => false];
        }

        $response = Http::withHeaders($headers)->get($url);

        Log::error('resp1', $response->json());

        if (!in_array($response->status(), [200, 201])) {
            return;
        }

        $token = $response->json('items.0.id');

        $status = match ($response->json('status')) {
            'succeeded', 'successful', 'success', 'approved'                        => Transaction::STATUS_PAID,
            'failed', 'cancelled', 'reversed', 'chargeback', 'disputed', 'rejected' => Transaction::STATUS_CANCELED,
            default                                                                 => Transaction::STATUS_PROGRESS,
        };

        return $this->service->afterHook($token, $status);

    }

}
