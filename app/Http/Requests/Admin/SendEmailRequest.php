<?php

namespace App\Http\Requests\Admin;

use App\Models\Email;
use Illuminate\Foundation\Http\FormRequest;

class SendEmailRequest extends FormRequest
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
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->email->isVersion(2)) {
            $this->merge([
                'scheduled_for' => $this->email->scheduled_for ?? null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->email->isVersion(2)) {
            return [
                'scheduled_for' => ['required', 'date', 'after:now'],
            ];
        }

        return [];
    }

    public function messages()
    {
        return [
            'scheduled_for.required' => trans('newsletter.validation.scheduled.required'),
            'scheduled_for.after' => trans('newsletter.validation.scheduled.past'),
        ];
    }
}
