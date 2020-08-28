<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Classes\LbrUserValidator;
use Illuminate\Validation\ValidationException;
use App\Rules\IpRanges;

class CreateUserRequest extends FormRequest
{
    protected $lbrUserValidator;

    protected function prepareForValidation()
    {
        $inputBrands = $this->input('brands');
        $inputBrandUsernames = $this->input('usernames');
        $inputBrandPasswords = $this->input('passwords');
        $this->lbrUserValidator = new LbrUserValidator(null, $inputBrands, $inputBrandUsernames, $inputBrandPasswords);
    }

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
        $u = ['password' => 'nullable|min:6'] + User::$rules;
        $u['email'][] = Rule::unique('users');

        $u = $this->lbrUserValidator->checkAndUpdateRules($u);
        $u['ip_ranges'] = [new IpRanges()];

        return $u;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        $messages = [];
        $messages = $this->lbrUserValidator->checkAndUpdateMessages($messages);
        return $messages;
    }
}
