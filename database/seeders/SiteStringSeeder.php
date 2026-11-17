<?php

namespace Database\Seeders;

use App\Models\UiString;
use App\Support\UiStrings;
use Illuminate\Database\Seeder;

/**
 * Interface strings added for the site shell and the Home, Catalog, Compare and Model pages
 * (they are not in the prototype's ui-strings.json). Only missing keys are created, so staff
 * edits survive re-seeding. The Russian and Uzbek texts still need a native-speaker review.
 */
class SiteStringSeeder extends Seeder
{
    public const STRINGS = [
        'site.skip' => [
            'ru' => 'Перейти к содержимому',
            'uz' => 'Asosiy mazmunga oʻtish',
            'en' => 'Skip to content',
        ],
        // Shown instead of a phone number while setting('contacts') is flagged is_placeholder.
        'site.contacts.tbc' => [
            'ru' => 'Телефон и Telegram: уточняются',
            'uz' => 'Telefon va Telegram: aniqlanmoqda',
            'en' => 'Phone and Telegram: to be confirmed',
        ],
        // Replaces "ftr.note" (a prototype notice).
        'site.ftr.note' => [
            'ru' => 'Характеристики взяты с сайта производителя rongtatech.com и могут уточняться.',
            'uz' => 'Texnik maʼlumotlar ishlab chiqaruvchining rongtatech.com saytidan olingan va aniqlashtirilishi mumkin.',
            'en' => 'Specifications are taken from the manufacturer’s site, rongtatech.com, and may be updated.',
        ],
        // Replaces "buy.lead" (which mentions the price).
        'site.buy.lead' => [
            'ru' => 'Весы продают и обслуживают дилеры в регионах. О наличии и сроках поставки узнайте у своего дилера.',
            'uz' => 'Tarozilarni hududlardagi dilerlar sotadi va ularga xizmat koʻrsatadi. Mavjudligi va yetkazib berish muddatini dileringizdan soʻrang.',
            'en' => 'Dealers in the regions sell and service the scales. Ask your dealer about availability and delivery time.',
        ],
        'site.file.pending' => [
            'ru' => 'Файл готовится',
            'uz' => 'Fayl tayyorlanmoqda',
            'en' => 'File coming soon',
        ],
        'site.m.nofiles' => [
            'ru' => 'Файлов для этой модели пока нет.',
            'uz' => 'Bu model uchun fayllar hali yoʻq.',
            'en' => 'No files for this model yet.',
        ],
        'site.view.photo' => [
            'ru' => 'Фото {n}',
            'uz' => 'Rasm {n}',
            'en' => 'Photo {n}',
        ],
        // Capacity filter values and the card fact that follows the filter.
        'val.cap.15' => ['ru' => "15\u{00A0}кг", 'uz' => "15\u{00A0}kg", 'en' => "15\u{00A0}kg"],
        'val.cap.30' => ['ru' => "30\u{00A0}кг", 'uz' => "30\u{00A0}kg", 'en' => "30\u{00A0}kg"],
        // Trust block material supplied in the admin panel.
        'tr.open' => ['ru' => 'Открыть', 'uz' => 'Ochish', 'en' => 'Open'],
        'tr.address' => ['ru' => 'Адрес', 'uz' => 'Manzil', 'en' => 'Address'],
        // Footer distributor badge, edited separately from the top strip's badge.dist.
        'ftr.dist' => [
            'ru' => 'Официальный дистрибьютор в Узбекистане: RSG',
            'uz' => 'Oʻzbekistondagi rasmiy distribyutor: RSG',
            'en' => 'Official distributor in Uzbekistan: RSG',
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
