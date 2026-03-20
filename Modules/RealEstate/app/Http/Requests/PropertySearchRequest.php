<?php
namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:property_categories,id',
            'type_id' => 'nullable|exists:property_types,id',
            'project_id' => 'nullable|exists:projects,id',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0|gte:price_min',
            'area_min' => 'nullable|numeric|min:0',
            'area_max' => 'nullable|numeric|min:0|gte:area_min',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'furnishing' => 'nullable|in:furnished,unfurnished,semi-furnished',
            'legal_status' => 'nullable|string',
            'transaction_type' => 'nullable|in:sell,rent',
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id',
            'is_vip' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'sort_by' => 'nullable|in:price,area,created_at,views,price_asc,price_desc,area_asc,area_desc,newest,popular',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ];
    }
}