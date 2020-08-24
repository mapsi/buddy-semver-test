<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PreviewArticleRequest extends FormRequest
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
            'brand' => ['required', 'string', 'exists:imports,uuid'],
            'title' => ['required', 'string'],
            'headline' => ['nullable', 'string'],
            'stand_first' => ['nullable', 'string'],
            'site_lead_precis' => ['nullable', 'string'],
            'email_precis' => ['nullable', 'string'],
            'authors' => ['nullable', 'array', 'min:1'],
                'authors.*' => ['bail', 'string', 'alpha_dash', 'size:36'],
            'sections' => ['nullable', 'array'],
                'sections.*.type' => ['required', 'in:plain_text,image_with_text'],
                'sections.*.data' => ['required', 'json'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
