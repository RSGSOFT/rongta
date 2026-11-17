<?php

namespace Database\Seeders;

use App\Models\UiString;
use App\Support\UiStrings;
use Illuminate\Database\Seeder;

/**
 * Interface strings added for the Dealers, Knowledge base and Partner pages.
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
        // Partner login
        'pa.err.login' => [
            'ru' => 'Неверный логин или пароль.',
            'uz' => 'Login yoki parol notoʻgʻri.',
            'en' => 'Wrong login or password.',
        ],
        'pa.err.throttle' => [
            'ru' => 'Слишком много попыток. Повторите через {s} с.',
            'uz' => 'Urinishlar juda koʻp. {s} soniyadan keyin qayta urinib koʻring.',
            'en' => 'Too many attempts. Try again in {s} seconds.',
        ],
        // Partner area
        'pa.hello' => [
            'ru' => 'Вы вошли как {name}.',
            'uz' => 'Siz {name} sifatida kirdingiz.',
            'en' => 'You are logged in as {name}.',
        ],
        'pa.res.h' => [
            'ru' => 'Что получают реселлеры',
            'uz' => 'Resellerlar nimalarga ega boʻladi',
            'en' => 'What resellers get',
        ],
        'pa.res.listing' => [
            'ru' => 'Размещение на странице «Дилеры»',
            'uz' => '«Dilerlar» sahifasida joylashtirish',
            'en' => 'Listing on the Dealers page',
        ],
        'pa.res.listing.d' => [
            'ru' => 'Ваша компания показана в разделе «Реселлеры» в своём регионе, где её ищут покупатели.',
            'uz' => 'Kompaniyangiz oʻz hududida «Resellerlar» boʻlimida koʻrsatiladi, xaridorlar uni shu yerdan qidiradi.',
            'en' => 'Your company appears under “Resellers” in your region, where buyers look for a dealer.',
        ],
        'pa.res.docs' => [
            'ru' => 'Руководства, драйверы и программы',
            'uz' => 'Qoʻllanmalar, drayverlar va dasturlar',
            'en' => 'Manuals, drivers and software',
        ],
        'pa.res.docs.d' => [
            'ru' => 'Материалы по каждой модели собраны в базе знаний.',
            'uz' => 'Har bir model boʻyicha materiallar bilimlar bazasida jamlangan.',
            'en' => 'Materials for every model are collected in the knowledge base.',
        ],
        'pa.res.contact' => [
            'ru' => 'Контакт в RSG',
            'uz' => 'RSG bilan aloqa',
            'en' => 'RSG contact',
        ],
        'pa.res.contact.d' => [
            'ru' => 'Вопросы по поставкам и условиям решаются напрямую с RSG.',
            'uz' => 'Yetkazib berish va shartlar boʻyicha savollar bevosita RSG bilan hal qilinadi.',
            'en' => 'Supply and terms questions go directly to RSG.',
        ],
        'pa.terms.label' => [
            'ru' => 'Условия для вашей компании',
            'uz' => 'Kompaniyangiz uchun shartlar',
            'en' => 'Terms for your company',
        ],
        'pa.terms.onreq' => [
            'ru' => 'по запросу',
            'uz' => 'soʻrov boʻyicha',
            'en' => 'on request',
        ],
        'pa.terms.note' => [
            'ru' => 'RSG согласует коммерческие условия с каждым партнёром отдельно. Чтобы получить их, свяжитесь с RSG.',
            'uz' => 'RSG tijorat shartlarini har bir hamkor bilan alohida kelishadi. Ularni olish uchun RSG bilan bogʻlaning.',
            'en' => 'RSG agrees commercial terms with each partner individually. Contact RSG to get yours.',
        ],
        'pa.contact' => [
            'ru' => 'Контакт RSG:',
            'uz' => 'RSG aloqa maʼlumoti:',
            'en' => 'RSG contact:',
        ],
        'pa.tokb' => [
            'ru' => 'Открыть базу знаний',
            'uz' => 'Bilimlar bazasini ochish',
            'en' => 'Open the knowledge base',
        ],
        'pa.todealers' => [
            'ru' => 'Открыть страницу «Дилеры»',
            'uz' => '«Dilerlar» sahifasini ochish',
            'en' => 'Open the Dealers page',
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
