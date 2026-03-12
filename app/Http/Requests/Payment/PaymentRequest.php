<?php
declare(strict_types=1);

namespace App\Http\Requests\Payment;

use ReflectionClass;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseRequest;
use App\Http\Requests\Order\StoreRequest;

class PaymentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $userId = auth('sanctum')->id();

        $tips           = request('tips');
        $cartId         = request('cart_id');
        $parcelId       = request('parcel_id');
        $subscriptionId = request('subscription_id');
        $adsPackageId   = request('ads_package_id');
        $walletId       = request('wallet_id');
        $auctionId      = request('auction_id');

        $rules = [];

        if ($cartId) {
            $rules = (new StoreRequest)->rules();
        }

        $reflectionClass = new ReflectionClass('Iyzipay\Model\PaymentChannel');
        $constants = $reflectionClass->getConstants();

        return [
            'cart_id' => [
                Rule::exists('carts', 'id')->where('owner_id', $userId)
            ],
            'booking_id' => [
                Rule::exists('bookings', 'id')->where('user_id', $userId)->when(!$tips || !request('extra_time'), fn($q) => $q->whereNull('parent_id'))
            ],
            'gift_cart_id' => [
                Rule::exists('gift_carts', 'id')->where('active', true)
            ],
            'member_ship_id' => [
                Rule::exists('member_ships', 'id')->where('active', true)
            ],
            'parcel_id' => [
                Rule::exists('parcel_orders', 'id')->where('user_id', $userId)
            ],
            'subscription_id' => [
                Rule::exists('subscriptions', 'id')->where('active', true)
            ],
            'ads_package_id' => [
                Rule::exists('ads_packages', 'id')->where('active', true)
            ],
            'wallet_id' => [
                Rule::exists('wallets', 'id')->where('user_id', auth('sanctum')->id())
            ],
            'auction_id' => [
                Rule::exists('auction_users', 'id')->where('user_id', auth('sanctum')->id())
            ],
            'total_price' => [
                'numeric'
            ],
            'from_wallet_price'  => 'numeric',
            'holder_name'  => 'string|min:5|max:255',
            'card_number'  => 'numeric',
            'expire_month' => 'numeric|max:12',
            'expire_year'  => 'int',
            'cvc' 		   => 'string|max:255',
            'chanel' 	   => 'string|in:' . implode(',', $constants),
            'phone'        => 'int',
            'email'        => 'string',
            'firstname'    => 'string',
            'lastname'     => 'string',
            'type'         => 'string|in:mtn,orange',
        ] + $rules;
    }

}
