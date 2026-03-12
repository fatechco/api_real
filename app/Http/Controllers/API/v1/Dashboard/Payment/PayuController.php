<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use Http;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\PaymentService\PayuService;
use Log;

class PayuController extends PaymentBaseController
{
    public function __construct(private PayuService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function paymentWebHook(Request $request): void
    {
        $data = $request->all();

        Log::error('$data', $data);
        $payment = Payment::with(['paymentPayload'])->where('tag', Payment::TAG_PAYU)->first();
        $payload = $payment?->paymentPayload?->payload ?? [];

        $token = $data['txnid'] ?? $data['merchantTxnId'];

        $sandbox = $payload['sandbox'] ?? false;
        $salt    = $payload['salt'] ?? 'rKEFige3l2cuVJYGBOkFKTE4FV6p7qwT';
        $baseUrl = ($sandbox ? 'https://test.payu.in' : 'https://info.payu.in');

        $postData = [
            'key'     => $payload['key'] ?? 'HVK2BE',
            'command' => 'verify_payment',
            'var1'    => $token,
        ];

        $postData['hash'] = hash('sha512', implode('|', $postData) . "|$salt");

        $response = Http::asForm()->withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])
            ->post("$baseUrl/merchant/postservice.php?form=2", $postData)
            ->json();

        $status = match (data_get($response, "transaction_details.$token.status")) {
            'success' => Transaction::STATUS_PAID,
            'failure' => Transaction::STATUS_CANCELED,
            default   => 'progress',
        };

        $this->service->afterHook($token, $status, $token);
    }

}
