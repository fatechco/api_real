<?php

namespace App\Listeners\Order;

use App\Http\Resources\OrderResource;
use Log;
use Http;
use Throwable;
use App\Models\Order;
use App\Events\Order\SendDeliveryManLocationByOrder;

class SendDeliveryManLocationByOrderListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param SendDeliveryManLocationByOrder $event
     * @return void
     */
    public function handle(SendDeliveryManLocationByOrder $event): void
    {
        $orders = Order::with([
            'shop' => fn($q) => $q->select([
                'id',
                'location',
                'tax',
                'price',
                'price_per_km',
                'background_img',
                'logo_img',
                'uuid',
                'phone',
                'delivery_time',
            ])->withAvg('reviews', 'rating'),
            'shop.translation' => fn($q) => $q->select('id', 'shop_id', 'locale', 'title')
                ->where('locale', $event->language),
            'shop.workingDays',
            'deliveryman:id,firstname,lastname',
            'deliveryman.deliveryManSetting:id,user_id,location',
            'orderDetails.stock.product:id,unit_id,img',
            'orderDetails.stock.product.unit.translation' => fn($q) => $q
                ->where('locale', $event->language),
            'orderDetails.stock.product.translation' => fn($ct) => $ct
                ->where('locale', $event->language),
            'orderDetails.stock.stockExtras.group.translation' => function ($cgt) use ($event) {
                $cgt->select('id', 'extra_group_id', 'locale', 'title')
                    ->where('locale', $event->language);
            },
        ])
            ->whereHas('deliveryman', fn($q) => $q->select('id'))
            ->whereHas('deliveryman.deliveryManSetting', fn($q) => $q->select('id', 'user_id', 'location'))
            ->select(['id', 'user_id', 'deliveryman_id'])
            ->find($event->orderIds);

        /** @var Order[] $orders */
        foreach ($orders as $order) {

            $setting = $order->deliveryman?->deliveryManSetting;

            $payload = [
                'type'   => 'driver_location',
                'target' => (string)$order->id,
                'data'   => [
                    'id'       => $order->id,
                    'user_id'  => $order->user_id,
                    'location' => $setting?->location,
                    'order'    => OrderResource::make($order)
                ],
                'timestamp' => now()->timestamp,
            ];

            $this->sendToSocket($payload);

        }
    }

    /**
     * @param array $payload
     * @return void
     */
    protected function sendToSocket(array $payload): void
    {
        $secret = config('app.ws_secret');
        $url    = config('app.ws_url');

        $signature = hash_hmac('sha256', json_encode([
            'type'      => $payload['type'],
            'target'    => $payload['target'],
            'timestamp' => $payload['timestamp'],
        ]), $secret);

        try {
//            Log::error('order location send', $payload);

            $r = Http::withHeaders(['Authorization' => $signature])->post("https://$url/api/socket", $payload);

//            Log::error('order location send', [
//                $r->status(),
//                $r->json()
//            ]);
        } catch (Throwable $e) {
            Log::error('WebSocket send failed', ['error' => $e->getMessage()]);
        }
    }
}
