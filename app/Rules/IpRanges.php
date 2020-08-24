<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IpRanges implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $totalMatches = preg_match_all("/(?'ipfrom'\b(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\b|\b(([a-fA-F]|[a-fA-F][a-fA-F0-9\-]*[a-fA-F0-9])\.)*([A-Fa-f]|[A-Fa-f][A-Fa-f0-9\-]*[A-Fa-f0-9])\b)(-(?'ipto'\b(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\b|\b(([a-fA-F]|[a-fA-F][a-fA-F0-9\-]*[a-fA-F0-9])\.)*([A-Fa-f]|[A-Fa-f][A-Fa-f0-9\-]*[A-Fa-f0-9])\b))?/m", $value);

        //remove empty lines
        $value = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $value);

        $lines = substr_count($value, PHP_EOL) + 1;

        return empty($value) || ($lines == $totalMatches);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The IP ranges are not valid. Please follow this format {starting_IP}-{ending_IP} or {single_IP}; every IP range must be on a different line.';
    }
}
