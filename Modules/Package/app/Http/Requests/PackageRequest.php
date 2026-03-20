<?php
namespace Modules\Package\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:member,agent,agency',
            'role_name' => 'required|string|max:255|unique:packages,role_name',
            'price' => 'required|numeric|min:0',
            'credits_per_month' => 'required|integer|min:0',
            'max_agents' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'limits' => 'required|array',
            'limits.listingsPerMonth' => 'required|integer|min:0',
            'limits.vipListings' => 'required|integer|min:0',
            'limits.teamMembers' => 'required|integer|min:0',
            'limits.apiCalls' => 'required|integer|min:0',
            'limits.storage' => 'required|integer|min:0',
            'features' => 'required|array',
            'features.*.code' => 'required|string',
            'features.*.enabled' => 'required|boolean',
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['role_name'] = 'required|string|max:255|unique:packages,role_name,' . $this->route('id');
        }

        return $rules;
    }
}