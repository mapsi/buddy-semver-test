<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class SaveEmailRequest extends FormRequest
{
    const SCHEDULE_FORMAT = 'Y-m-d H:i';
    const SCHEDULE_TIMEZONE = 'Europe/London';

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'scheduled_for' => $this->formatScheduledForFromRequest(),
        ]);
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
        $commonRules = [
            'subject' => ['required', 'string'],
            'brand' => ['required', 'exists:brands,machine_name'],
            'type' => ['required', 'in:weekly,daily'],
            'scheduled_for' => [
                'nullable',
                'date',
                'after:now',
            ],
            'send_date' => 'required_with:send_time.HH,send_time.mm',
            'send_time.HH' => 'required_with:send_date,send_time.mm',
            'send_time.mm' => 'required_with:send_date,send_time.HH',
        ];

        $emailConfig = $this->loadTheRightEmailConfig($this->input('brand'), $this->input('type'));

        if (! $emailConfig) {
            throw new \LogicException('Config for email brand and type do not exist');
        }

        return array_merge($commonRules, $emailConfig);
    }

    public function messages()
    {
        return [
            'scheduled_for.after' => trans('newsletter.validation.scheduled.past'),
        ];
    }

    /**
     * @return array|\Illuminate\Config\Repository|mixed
     */
    protected function loadTheRightEmailConfig()
    {
        return config("emails.content.{$this->input('brand')}_{$this->input('type')}.validation_rules");
    }

    /**
     * @param string|null $date
     * @param string|null $time
     * @return string
     */
    private function formatScheduledForFromRequest()
    {
        $sendDate = $this->input('send_date', null);
        $sendTime = $this->input('send_time', []);

        if (! $sendDate || $this->checkIfNestedNullAttributes($sendTime)) {
            return null;
        }

        $hour = Arr::get($sendTime, 'HH');
        $minute = Arr::get($sendTime, 'mm');

        return Carbon::parse($sendDate)
            ->setTimezone(static::SCHEDULE_TIMEZONE)
            ->hour($hour)
            ->minute($minute)
            ->setTimezone('UTC');
    }

    private function checkIfNestedNullAttributes(array $array)
    {
        return count(array_filter($array, 'is_null')) == count($array);
    }
}
