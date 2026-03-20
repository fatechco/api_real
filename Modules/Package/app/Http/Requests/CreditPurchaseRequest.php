<?php
namespace Modules\Package\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreditPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:10|max:10000',
            'payment_method' => 'required|string|in:vnpay,momo,paypal,stripe',
        ];
    }
}