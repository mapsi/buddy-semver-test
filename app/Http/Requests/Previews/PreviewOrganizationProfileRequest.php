<?php

namespace App\Http\Requests\Previews;

use Illuminate\Foundation\Http\FormRequest;

class PreviewOrganizationProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'brand_code' => 'required',

            'name' => 'required',
            'body' => 'nullable',
            'published_at' => 'nullable',
            'region_country' => 'nullable|array',

            'regions' => 'nullable|array',
            'regions.clients' => 'nullable',
            'regions.head' => 'nullable',
            'regions.size' => 'nullable',
        ];
    }
}
