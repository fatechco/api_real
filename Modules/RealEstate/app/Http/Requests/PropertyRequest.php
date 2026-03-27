<?php
namespace Modules\RealEstate\Http\Requests;

use App\Http\Requests\BaseRequest;

class PropertyRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Translations
            'translations' => 'required|array',
            //'translations.*.locale' => 'required|string|in:en,vi,fr,zh,ko',
            'translations.*.title' => 'required|string|max:255',
            'translations.*.description' => 'required|string',
            'translations.*.content' => 'nullable|string',
            
            // Basic info
            'category_id' => 'nullable|exists:property_categories,id',
            'type' => 'required|in:sale,rent',
            'status' => 'nullable|in:pending,available,sold,rented',
            'price' => 'required|numeric|min:0',
            'is_negotiable' => 'boolean',
            
            // Area
            'area' => 'required|numeric|min:0',
            'land_area' => 'nullable|numeric|min:0',
            'built_area' => 'nullable|numeric|min:0',
            
            // Details
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'floors' => 'nullable|integer|min:0',
            'garages' => 'nullable|integer|min:0',
            'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
            'furnishing' => 'nullable|string|in:unfurnished,semi-furnished,furnished,fully-furnished',
            'legal_status' => 'nullable|string|max:100',
            'ownership_type' => 'nullable|string|max:100',
            
            // Location
            'country_id' => 'nullable|exists:countries,id',
            'province_id' => 'nullable|exists:provinces,id',
            'district_id' => 'nullable|exists:districts,id',
            'ward_id' => 'nullable|exists:wards,id',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:50',
            'building_name' => 'nullable|string|max:255',
            'full_address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
            // Media
            'images' => 'nullable|array',
            'images.*' => 'file|image|mimes:jpeg,png,jpg,webp|max:10240',
            'video_url' => 'nullable|url|max:500',
            'virtual_tour_url' => 'nullable|url|max:500',
            
            // Amenities
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id',
        ];
    }

    public function messages(): array
    {
        return [
            'translations.required' => 'Translations are required',
            'translations.*.title.required' => 'Title is required',
            'translations.*.description.required' => 'Description is required',
            'price.required' => 'Price is required',
            'area.required' => 'Area is required',
            'type.required' => 'Property type is required',
            'images.*.image' => 'File must be an image',
            'images.*.max' => 'Image size cannot exceed 10MB',
        ];
    }
}