<?php

namespace App\Helpers;

class TextHelper
{
    public function getFirstSentenceOfText(string $text)
    {
        $firstSentence = preg_replace('/([^?!.]*.).*/', '\\1', $text);

        return $firstSentence;
    }
}
