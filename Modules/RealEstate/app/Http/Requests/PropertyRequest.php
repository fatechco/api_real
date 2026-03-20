<?php
namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|array',
            'title.en' => 'required|string|max:255',
            'title.vi' => 'nullable|string|max:255',
            'description' => 'required|array',
            'description.en' => 'required|string',
            'description.vi' => 'nullable|string',
            'content' => 'nullable|array',
            
            'project_id' => 'nullable|exists:projects,id',
            'category_id' => 'nullable|exists:property_categories,id',
            'type_id' => 'nullable|exists:property_types,id',
            
            'price' => 'required|numeric|min:0',
            'price_per_m2' => 'nullable|numeric|min:0',
            'is_negotiable' => 'boolean',
            
            'area' => 'required|numeric|min:0',
            'land_area' => 'nullable|numeric|min:0',
            'built_area' => 'nullable|numeric|min:0',
            
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'floors' => 'nullable|integer|min:0',
            'garages' => 'nullable|integer|min:0',
            'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
            'furnishing' => 'nullable|in:furnished,unfurnished,semi-furnished',
            'legal_status' => 'nullable|string|max:100',
            'ownership_type' => 'nullable|string|max:100',
            
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'ward' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:100',
            'project_name' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'map_url' => 'nullable|url|max:500',
            
            'status' => 'nullable|in:pending,available,sold,rented,expired,hidden',
            'transaction_type' => 'nullable|in:sell,rent',
            
            'is_featured' => 'boolean',
            'is_vip' => 'boolean',
            'vip_days' => 'required_if:is_vip,true|integer|in:3,7,14,30',
            'is_urgent' => 'boolean',
            'is_top' => 'boolean',
            'top_days' => 'required_if:is_top,true|integer|in:3,7,14,30',
            
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
            'image_ids' => 'nullable|array',
            'image_ids.*' => 'exists:property_images,id',
            
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id',
            
            'meta_title' => 'nullable|array',
            'meta_description' => 'nullable|array',
            'meta_keywords' => 'nullable|array'
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['title'] = 'sometimes|array';
            $rules['price'] = 'sometimes|numeric|min:0';
            $rules['area'] = 'sometimes|numeric|min:0';
            $rules['address'] = 'sometimes|string|max:255';
            $rules['city'] = 'sometimes|string|max:100';
            $rules['district'] = 'sometimes|string|max:100';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required',
            'title.en.required' => 'English title is required',
            'description.required' => 'Description is required',
            'description.en.required' => 'English description is required',
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a number',
            'area.required' => 'Area is required',
            'area.numeric' => 'Area must be a number',
            'address.required' => 'Address is required',
            'city.required' => 'City is required',
            'district.required' => 'District is required',
            'images.*.image' => 'File must be an image',
            'images.*.max' => 'Image size cannot exceed 5MB',
            'vip_days.required_if' => 'Please select number of days for VIP',
            'top_days.required_if' => 'Please select number of days for Top'
        ];
    }
}