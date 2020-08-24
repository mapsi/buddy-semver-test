<?php

namespace App\Classes;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Language
{
    /**
     * @param string $key
     * @param        $args
     * @return string
     */
    public static function get(string $key, array $args = [])
    {
        $translation = self::getTranslationFromBrand($key, $args);
        return self::isValid($translation, $key) ? $translation : self::getFallbackTranslation($key, $args);
    }

    /**
     * @param string $key
     * @param array  $args
     * @return string|null
     */
    private static function getTranslationFromBrand(string $key, array $args = [])
    {
        return trans(active_host() . "::int.{$key}", $args);
    }

    /**
     * @param string $key
     * @param array  $args
     * @return string
     */
    private static function getFallbackTranslation(string $key, array $args = [])
    {

        $fallback = trans("int.$key", $args);

        if (self::isValid($fallback, $key)) {
            return $fallback;
        }
        Log::warning("Translation with key '$key' is missing");

        return '';
    }

    /**
     * @param string $translation
     * @param string $key
     * @return bool
     */
    private static function isValid(string $translation, string $key)
    {
        if (Str::contains($translation, ['int.', 'int/'])) {
            return ! Str::contains($translation, $key);
        }

        return true;
    }
}
