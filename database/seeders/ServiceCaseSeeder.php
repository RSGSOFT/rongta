<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\ServiceCase;
use Illuminate\Database\Seeder;

/**
 * Starter service jobs, without amounts: staff enter what RSG charges, and a job without an
 * amount stays off the site. Created only while the table is empty, so staff edits and
 * deletions survive a re-run. The Russian and Uzbek texts need a native-speaker review.
 *
 * Sources (owner-approved list, 2026-11-24):
 *  - "label": troubleshooting chapter of Rongta's "RLS Series User manual V1.3".
 *  - "ai": troubleshooting chapter of Rongta's "AURORA PC Series User manual V1.5" and the
 *    manuals of sibling AURORA models (no manual is published for C2-AI, D2-AI or Y2L-AI).
 *  - "comm": three jobs from Rongta's "C1 Pos Series User manual V1.0" (seen as excerpts only).
 *  - Jobs marked "RSG" below are not from a manual; the owner added them.
 */
class ServiceCaseSeeder extends Seeder
{
    /** Family slug (null = every scale) => list of [ru, uz, en]. */
    public const ITEMS = [
        null => [
            // RSG
            ['Диагностика (поиск неисправности)', 'Diagnostika (nosozlikni aniqlash)', 'Diagnostics (fault finding)'],
            ['Профилактическая чистка и обслуживание', 'Profilaktik tozalash va xizmat koʻrsatish', 'Preventive cleaning and maintenance'],
            ['Подготовка к государственной метрологической поверке', 'Davlat metrologik qiyoslashiga tayyorlash', 'Preparation for state metrology verification'],
            ['Настройка связи с кассовой программой', 'Kassa dasturi bilan aloqani sozlash', 'Cash register software connection setup'],
        ],
        'label' => [
            ['Весы не включаются: ремонт шнура, предохранителя или платы питания', 'Tarozi yoqilmaydi: sim, saqlagich yoki taʼminot platasini taʼmirlash', 'Scale does not switch on: power cord, fuse or power board repair'],
            ['Замена материнской платы с установкой номера весов и загрузкой данных', 'Asosiy platani almashtirish, tarozi raqamini oʻrnatish va maʼlumotlarni yuklash', 'Motherboard replacement, with scale number set and data reloaded'],
            ['Перезагрузка прошивки (весы зависли на «Update»)', 'Proshivkani qayta yuklash (tarozi «Update»da qotib qolgan)', 'Firmware reload (scale stuck on “Update”)'],
            ['Чистка и регулировка печатающей головки (бледная или нечёткая печать)', 'Chop etish kallagini tozalash va sozlash (xira yoki noaniq chop)', 'Print head cleaning and adjustment (faint or fuzzy print)'],
            ['Замена печатающей головки', 'Chop etish kallagini almashtirish', 'Print head replacement'],
            ['Замена шлейфа печатающей головки', 'Chop etish kallagi shleyfini almashtirish', 'Print head cable replacement'],
            ['Чистка, регулировка или замена датчика зазора этикеток (половина этикетки, сдвиг печати)', 'Yorliq oraligʻi datchigini tozalash, sozlash yoki almashtirish (yorliqning yarmi, chop siljishi)', 'Label gap sensor cleaning, alignment or replacement (half label, wrong position)'],
            ['Чистка резинового валика', 'Rezina valikni tozalash', 'Rubber roller cleaning'],
            ['Ремонт подачи бумаги (замятие, вал намотки, шестерни)', 'Qogʻoz uzatishni taʼmirlash (tiqilish, oʻrash vali, tishli gʻildiraklar)', 'Paper feed repair (jam, take-up shaft, gears)'],
            ['Ремонт или замена клавиатуры (кнопки не работают, постоянный сигнал)', 'Klaviaturani taʼmirlash yoki almashtirish (tugmalar ishlamaydi, uzluksiz signal)', 'Keyboard repair or replacement (dead keys, constant beep)'],
            ['Калибровка: ноль и полный вес, поправка на ускорение свободного падения', 'Kalibrlash: nol va toʻliq vazn, erkin tushish tezlanishiga tuzatish', 'Calibration: zero and full weight, gravity adjustment'],
            ['Замена тензодатчика', 'Tenzodatchikni almashtirish', 'Load cell replacement'],
            ['Замена платы АЦП (AD)', 'AD platasini almashtirish', 'AD board replacement'],
            ['Ремонт дисплея или стойки дисплея', 'Displey yoki displey ustunini taʼmirlash', 'Display or display pole repair'],
            ['Нет связи с компьютером (порт, кабель, плата связи)', 'Kompyuter bilan aloqa yoʻq (port, kabel, aloqa platasi)', 'PC connection fault (port, cable, communication board)'],
            ['Настройка сети и Wi-Fi', 'Tarmoq va Wi-Fi ni sozlash', 'Network and Wi-Fi setup'],
            ['Загрузка товаров (PLU), горячих клавиш, форматов этикеток и шрифтов', 'Tovarlar (PLU), tezkor tugmalar, yorliq formatlari va shriftlarni yuklash', 'Loading PLU, hot keys, label formats and fonts'],
            ['Сброс к заводским настройкам и очистка данных', 'Zavod sozlamalariga qaytarish va maʼlumotlarni tozalash', 'Factory reset and data clearing'],
            ['Настройка печати (тип этикетки, плотность, положение)', 'Chop etishni sozlash (yorliq turi, zichlik, joylashuv)', 'Print setup (label type, density, position)'],
            // RSG
            ['Сброс паролей функций', 'Funksiya parollarini tiklash', 'Function password reset'],
        ],
        'ai' => [
            ['Весы не запускаются: кабель питания, плата питания или основная плата', 'Tarozi ishga tushmaydi: taʼminot kabeli, taʼminot platasi yoki asosiy plata', 'Scale does not start: power cable, power board or main board'],
            ['Ремонт или замена основного экрана или экрана покупателя', 'Asosiy yoki xaridor ekranini taʼmirlash yoki almashtirish', 'Main or customer screen repair or replacement'],
            ['Ремонт сенсорного экрана или переустановка драйвера', 'Sensorli ekranni taʼmirlash yoki drayverni qayta oʻrnatish', 'Touch screen repair or driver reinstall'],
            ['Неисправность принтера: драйвер или плата печати', 'Printer nosozligi: drayver yoki chop etish platasi', 'Printer fault: driver or print board'],
            ['Неисправность взвешивания: плата АЦП (AD), тензодатчик или перезагрузка весовой программы', 'Tortish nosozligi: AD platasi, tenzodatchik yoki tortish dasturini qayta yuklash', 'Weighing fault: AD board, load cell or weighing software reload'],
            ['Установка: снятие транспортировочных винтов, выравнивание весов', 'Oʻrnatish: transport vintlarini olib tashlash, tarozini tekislash', 'Installation: transport screws removed, scale levelled'],
            ['Поправка на ускорение свободного падения', 'Erkin tushish tezlanishiga tuzatish', 'Gravity adjustment'],
            // RSG
            ['Ремонт или замена камеры', 'Kamerani taʼmirlash yoki almashtirish', 'Camera repair or replacement'],
            ['Переустановка системы и обновление приложений', 'Tizimni qayta oʻrnatish va ilovalarni yangilash', 'System reinstall and app update'],
            ['Настройка библиотеки товаров для распознавания ИИ', 'Sunʼiy intellekt tanishi uchun tovarlar kutubxonasini sozlash', 'AI product library setup'],
        ],
        'comm' => [
            // "C1 Pos Series User manual V1.0", chapter 6: read only as search-engine excerpts, the file would not open.
            ['Весы не включаются: кабель питания, адаптер или основная плата', 'Tarozi yoqilmaydi: taʼminot kabeli, adapter yoki asosiy plata', 'Scale does not switch on: power cable, adapter or main board'],
            ['Ремонт или замена дисплея', 'Displeyni taʼmirlash yoki almashtirish', 'Display repair or replacement'],
            ['Неисправность взвешивания: основная плата, плата АЦП (AD) или тензодатчик', 'Tortish nosozligi: asosiy plata, AD platasi yoki tenzodatchik', 'Weighing fault: main board, AD board or load cell'],
            // RSG (no manual found for C11 or C3)
            ['Вес не передаётся на кассу (порт, скорость обмена, кабель)', 'Vazn kassaga uzatilmaydi (port, almashinuv tezligi, kabel)', 'Weight is not sent to the cash register (port, baud rate, cable)'],
            ['Калибровка', 'Kalibrlash', 'Calibration'],
            ['Ремонт или замена клавиатуры', 'Klaviaturani taʼmirlash yoki almashtirish', 'Keyboard repair or replacement'],
            ['Замена кабеля связи или разъёма', 'Aloqa kabeli yoki ulagichini almashtirish', 'Communication cable or connector replacement'],
        ],
    ];

    public function run(): void
    {
        if (ServiceCase::query()->exists()) {
            return;
        }

        $families = Family::pluck('id', 'slug');
        $position = 0;

        foreach (self::ITEMS as $slug => $items) {
            // PHP turns the null array key into '': that group applies to every scale.
            $familyId = $slug === '' ? null : ($families[$slug] ?? false);
            if ($familyId === false) {
                continue;
            }

            foreach ($items as [$ru, $uz, $en]) {
                ServiceCase::create([
                    'family_id' => $familyId,
                    'title' => ['ru' => $ru, 'uz' => $uz, 'en' => $en],
                    'sort' => ++$position,
                    'is_published' => true,
                ]);
            }
        }
    }
}
