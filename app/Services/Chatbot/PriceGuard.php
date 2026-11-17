<?php

namespace App\Services\Chatbot;

/**
 * The site shows no prices. This spots a money amount so that such text is kept out of the
 * chatbot's knowledge and out of its replies.
 *
 * It errs on the side of blocking in sales language (a price word, a currency, "million",
 * a bare seven-digit figure) and stays quiet on technical text: a number followed by a unit
 * or by something that is counted (15 kg, 203 dpi, 10 000 products) is never an amount, and
 * phone numbers, dates, sizes, addresses and model names are taken out before the check.
 */
class PriceGuard
{
    // Uzbek takes endings (soʻmdan, soʻmga); Russian "сум" must not match "сумма".
    private const CURRENCY = '(?:сум(?:ов|а|ах|ы)?(?![\p{L}])|сўм|so[ʻʼ\'’`]m|som(?:dan|ga|lik|ni)?(?![\p{L}])'
        .'|uzs(?![\p{L}])|usd(?![\p{L}])|eur(?![\p{L}])|у\.\s?е\.|доллар|dollar|руб(?:л|\.)|евро)';

    /** Thousand, million, billion in the three site languages (and Uzbek Cyrillic). Atomic: no giving back. */
    private const MAGNITUDE = '(?>тыс(?>яч\p{L}*|\.|(?![\p{L}]))|млн(?![\p{L}])\.?|пол[\- ]?миллион\p{L}*|миллион\p{L}*|млрд(?![\p{L}])\.?|миллиард\p{L}*'
        .'|минг(?![\p{L}])|ming(?![\p{L}])|mln(?![\p{L}])\.?|mlrd(?![\p{L}])\.?|million\p{L}*|milliard\p{L}*|billion\p{L}*|thousand(?![\p{L}]))';

    /** Thousands separators: space, no-break spaces, dot, comma, apostrophe. */
    private const SEP = '[ \x{00A0}\x{202F}\x{2009}.,\']';

    /** A number: 5 500 000, 5,500,000, 5.5, 4625000. */
    private const NUMBER = '\d+(?:'.self::SEP.'\d+)*';

    /** A number written with thousands separators and at least seven digits: 5 500 000. */
    private const MILLIONS = '(?<![\p{L}\d.,])(?<!\d[ \x{00A0}])(?>\d{1,3}(?:'.self::SEP.'\d{3}){2,})(?![\d])';

    /** A number of three digits or more, grouped or not: 450, 5500, 5 500 000. */
    private const SUM = '(?<![\p{L}\d.,])(?<!\d[ \x{00A0}])(?>(?:\d{1,3}(?:'.self::SEP.'\d{3})+|\d{3,})(?:[.,]\d{1,2})?)(?![\d])';

    /** Numbers written as words, as far as they come before "million" or "thousand". */
    private const NUMBER_WORD = '(?:один|одн\p{L}+|дв[ае]|двух|три|тр[её]х|четыр\p{L}+|пят\p{L}+|шест\p{L}+|сем\p{L}+|восем\p{L}+|восьм\p{L}+'
        .'|девят\p{L}+|десят\p{L}+|сорок\p{L}*|сто|ста|двести|триста|пол|полтора|полутора|половин\p{L}+'
        .'|bir|ikki|uch|to[ʻʼ\'’`]rt|besh|olti|yetti|sakkiz|to[ʻʼ\'’`]qqiz|o[ʻʼ\'’`]n|yigirma|o[ʻʼ\'’`]ttiz|qirq|ellik|yuz|yarim'
        .'|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|twenty|thirty|forty|fifty|sixty|seventy|eighty|ninety|hundred|half)';

    /** Words that announce a price. */
    private const PRICE_WORD = '(?:цен(?:а|ы|у|е|ой|ам|ами|ах|ник\p{L}*)?(?![\p{L}])|стоимост\p{L}*|стои[тл]\p{L}*|стоят(?![\p{L}])|стоить|обойд[её]тся|обходится|прайс\p{L}*|скидк\p{L}*'
        .'|narx\p{L}*|нарх\p{L}*|turadi|туради|chegirma\p{L}*'
        .'|price[sd]?(?![\p{L}])|pricing|costs?(?![\p{L}])|costing|discount\p{L}*)';

    /** Price wording that comes after the number: "4 625 000 за штуку". */
    private const PRICE_TAIL = '(?:за\s+(?:штуку|шт(?![\p{L}])|единицу|комплект|одну|один|кажд\p{L}+)|per\s+(?:unit|piece|scale|item)|each(?![\p{L}])|apiece|turadi|туради|donasi)';

    /**
     * What may follow a number in technical text: a unit, or a thing that is counted.
     * The case-sensitive part keeps "В" (volt) apart from the Russian preposition "в".
     */
    private const NOT_MONEY = '(?:\s*(?:%|°|(?-i:В|Вт|кВт|А|мА|мАч|Ач|V|W|kW|A|mA|mAh|Ah)(?![\p{L}])'
        .'|(?:кг|г|гр|мг|мм|см|м|км|мс|сек|мин|час\p{L}*|гц|кгц|мгц|ггц|dpi|kg|g|mg|mm|cm|m|km|ms|s|sec|min|h|hours?|hz|khz|mhz|ghz'
        .'|[kmgt]?b|[кмгт]б|байт\p{L}*|bytes?|bits?|бит|бод|baud|bps|px|pixels?|шт|dona|ta|pcs|lb|oz|soat|daqiqa)(?![\p{L}])'
        .'|(?:дн(?:я|ей|и)(?![\p{L}])|день|сут|недел|месяц|лет(?![\p{L}])|год|days?(?![\p{L}])|weeks?(?![\p{L}])|months?(?![\p{L}])|years?(?![\p{L}])|kun|hafta|oy(?![\p{L}])|yil'
        .'|магазин|покупател|клиент|импульс|shop|store|customer|pulse|do[ʻʼ\'’`]kon|xaridor|mijoz'
        .'|товар|наимен|позиц|запис|этикет|чек|рез|срез|отрез|цикл|нажат|взвеш|операц|строк|клавиш|символ|знак|точ[ек]|пикс|артикул|продукт|раз(?![\p{L}])'
        .'|plu|sku|product|item|record|label|receipt|cut|cycle|press|weigh|operation|line|key|character|dot|times'
        .'|mahsulot|tovar|yozuv|yorliq|chek|kesish|sikl|tugma|belgi|qator|marta|nuqta)))';

    public static function hasAmount(?string $text): bool
    {
        if ($text === null || trim($text) === '') {
            return false;
        }

        // A number next to a currency, in either order, or a currency sign.
        if (preg_match('/\d[\d\s.,]*\s*(?:тыс\w*\.?|млн\.?|ming|mln|k|m)?\s*'.self::CURRENCY.'/iu', $text)
            || preg_match('/(?<![\p{L}])'.self::CURRENCY.'\s*\d/iu', $text)
            || preg_match('/[$€₽£¥]\s*\d|\d\s*[$€₽£¥]/u', $text)) {
            return true;
        }

        // "million soʻm" with the number spelled out.
        if (preg_match('/'.self::MAGNITUDE.'\s+'.self::CURRENCY.'/iu', $text)) {
            return true;
        }

        // The reseller offer is not public either.
        if (preg_match('/(?<![\d.,])10\s*\+\s*1(?![\d.,])/u', $text)) {
            return true;
        }

        $text = self::withoutTechnicalNumbers($text);
        $gap = '(?:[\s:—–\-=~≈«»"“”·]+[^\s\d.,;:!?()]+){0,5}[\s:—–\-=~≈«»"“”·]*';
        $counted = '(?!'.self::NOT_MONEY.')';

        return
            // 5.5 млн, 5,5 миллиона, "пять с половиной миллионов", unless something is being counted.
            (bool) preg_match('/(?:(?<![\p{L}\d])'.self::NUMBER.'\s*|(?<![\p{L}])'.self::NUMBER_WORD.'(?:\s+(?:с\s+половиной|yarim|and\s+a\s+half|a))?\s+|(?<![\p{L}])(?=пол))'.self::MAGNITUDE.$counted.'/iu', $text)
            // A bare 5 500 000.
            || (bool) preg_match('/'.self::MILLIONS.$counted.'/iu', $text)
            // A price word and then a figure: "Цена 5 500 000", "narxi 5 500 000", "price is 450".
            || (bool) preg_match('/(?<![\p{L}])'.self::PRICE_WORD.$gap.self::SUM.$counted.'/iu', $text)
            || (bool) preg_match('/(?<![\p{L}])'.self::PRICE_WORD.$gap.'(?:'.self::NUMBER_WORD.'\s+)?'.self::MAGNITUDE.$counted.'/iu', $text)
            // A figure and then price wording: "4 625 000 за штуку".
            || (bool) preg_match('/'.self::SUM.'\s*(?:[—–\-]\s*)?'.self::PRICE_TAIL.'/iu', $text);
    }

    /**
     * Takes out the numbers that are plainly not money, so a price word in the same sentence
     * does not turn them into an amount.
     */
    private static function withoutTechnicalNumbers(string $text): string
    {
        $patterns = [
            // Scale division is "цена деления" in Russian: a metrology term, not a price.
            '/цен\p{L}+\s+(?:поверочного\s+)?делени\p{L}+/iu',
            '/https?:\/\/\S+/iu',
            // Phone numbers: +998 90 123 45 67, (71) 200-00-00.
            '/\+\d[\d\s\-()]{7,}\d/u',
            '/(?<![\d.,])\(?\d{2}\)?[\s\-]\d{3}[\s\-]\d{2}[\s\-]\d{2}(?![\d])/u',
            // Network addresses and version numbers, then dates.
            '/(?<![\d.])\d{1,3}(?:\.\d{1,3}){3}(?![\d.])/u',
            '/(?<![\d.])\d{1,2}[.\/]\d{1,2}[.\/]\d{2,4}(?![\d])/u',
            '/(?<![\d])\d{4}-\d{2}-\d{2}(?![\d])/u',
            // Sizes and resolutions: 433 × 372 × 506, 1366 x 768.
            '/\d+(?:[.,]\d+)?(?:\s*[×xх*]\s*\d+(?:[.,]\d+)?)+/iu',
            // Ranges of a technical value: 100–240 V, 50/60 Hz.
            '/\d+(?:[.,]\d+)?\s*[–—\-\/]\s*\d+(?:[.,]\d+)?(?='.self::NOT_MONEY.')/iu',
            // Model names: RLS1000, RLS1100A, RLS 1515, C2-AI.
            '/(?<![\p{L}\d])(?-i:[A-Z]{1,6}[\- ]?\d{1,5}[A-Z]{0,3})(?![\p{L}\d])(?![ .,\x{00A0}]\d{3})/u',
            // Years, when the text says it is one: "2026 год", "2026-yil", "© 2026".
            '/(?<![\d.,])(?<!\d\s)(?:19|20)\d{2}(?=\s*(?:г\.|гг|год\p{L}*|[\- ]?yil|years?(?![\p{L}])))/iu',
            '/©\s*(?:19|20)\d{2}(?![\d])/u',
        ];

        return preg_replace($patterns, ' · ', $text) ?? $text;
    }
}
