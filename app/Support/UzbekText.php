<?php

namespace App\Support;

/**
 * Puts the right Uzbek Latin apostrophes into free text, such as chatbot replies.
 *
 * The site writes oʻ and gʻ with ʻ (U+02BB) and the tutuq belgisi with ʼ (U+02BC). Text from
 * other sources often uses ‘ ’ ' or ` instead. After o or g those become ʻ; between two other
 * letters they become ʼ (maʼlumot). A ʼ that is already there is left alone, because after o
 * it can be correct (moʼjiza).
 */
class UzbekText
{
    private const WRONG = "\u{2018}\u{2019}'`";

    public static function normalize(string $text): string
    {
        $wrong = preg_quote(self::WRONG, '/');

        $text = (string) preg_replace('/(?<=[oOgG])['.$wrong.']/u', "\u{02BB}", $text);

        return (string) preg_replace('/(?<=\p{L})['.$wrong.'](?=\p{L})/u', "\u{02BC}", $text);
    }
}
