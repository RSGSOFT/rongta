<?php

namespace Database\Seeders;

use App\Models\UiString;
use App\Support\UiStrings;
use Illuminate\Database\Seeder;

/**
 * Interface strings added for the Dealers and Knowledge base pages.
 * Only missing keys are created, so staff edits survive a re-run.
 * RU and UZ texts need a native-speaker review.
 */
class SiteBStringSeeder extends Seeder
{
    public const STRINGS = [
        // Dealers
        'dl.contact.tbc' => [
            'ru' => 'Телефон: уточняется',
            'uz' => 'Telefon: aniqlanmoqda',
            'en' => 'Phone: to be confirmed',
        ],
        'dl.sample.contact' => [
            'ru' => 'Это пример карточки, а не действующая компания. Обращайтесь в RSG.',
            'uz' => 'Bu namuna karta, haqiqiy kompaniya emas. RSG ga murojaat qiling.',
            'en' => 'This is a sample card, not a real company. Contact RSG instead.',
        ],
        // Become a partner form
        'pf.sent.h' => [
            'ru' => 'Заявка отправлена',
            'uz' => 'Soʻrov yuborildi',
            'en' => 'Request sent',
        ],
        'pf.sent.p' => [
            'ru' => 'RSG получила вашу заявку и свяжется с вами по указанному телефону.',
            'uz' => 'RSG soʻrovingizni qabul qildi va koʻrsatilgan telefon orqali siz bilan bogʻlanadi.',
            'en' => 'RSG has received your request and will contact you at the phone number you gave.',
        ],
        'pf.err.throttle' => [
            'ru' => 'Слишком много заявок подряд. Попробуйте позже.',
            'uz' => 'Ketma-ket juda koʻp soʻrov yuborildi. Keyinroq urinib koʻring.',
            'en' => 'Too many requests in a row. Please try again later.',
        ],
        'pf.err.long' => [
            'ru' => 'Текст слишком длинный.',
            'uz' => 'Matn juda uzun.',
            'en' => 'The text is too long.',
        ],
        'pf.err.choice' => [
            'ru' => 'Выберите значение из списка.',
            'uz' => 'Roʻyxatdan qiymat tanlang.',
            'en' => 'Choose a value from the list.',
        ],
        // Knowledge base
        'kb.soon' => [
            'ru' => 'Скоро',
            'uz' => 'Tez orada',
            'en' => 'Coming soon',
        ],
        'kb.video.watch' => [
            'ru' => 'Смотреть видео',
            'uz' => 'Videoni koʻrish',
            'en' => 'Watch the video',
        ],
        'kb.reset' => [
            'ru' => 'Сбросить поиск',
            'uz' => 'Qidiruvni tozalash',
            'en' => 'Reset the search',
        ],
    ];

    public function run(): void
    {
        foreach (self::STRINGS as $key => $text) {
            UiString::firstOrCreate(['key' => $key], $text);
        }

        UiStrings::flush();
    }
}
