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
        // For partners page
        'pp.h' => [
            'ru' => 'Партнёрство с RSG',
            'uz' => 'RSG bilan hamkorlik',
            'en' => 'Partner with RSG',
        ],
        'pp.adv.h' => [
            'ru' => 'Преимущества партнёрства',
            'uz' => 'Hamkorlik afzalliklari',
            'en' => 'Partner advantages',
        ],
        'pp.terms.h' => [
            'ru' => 'Условия партнёрства',
            'uz' => 'Hamkorlik shartlari',
            'en' => 'Partnership terms',
        ],
        // Service list in the Knowledge base. Owner-approved exception to 'no prices' (2026-11-24): service fees only.
        'svc.h' => [
            'ru' => 'Сервис и ремонт',
            'uz' => 'Servis va taʼmirlash',
            'en' => 'Service and repair',
        ],
        'svc.lead' => [
            'ru' => 'Работы по обслуживанию и ремонту весов Rongta и тарифы на них.',
            'uz' => 'Rongta tarozilariga xizmat koʻrsatish va taʼmirlash ishlari hamda ularning tariflari.',
            'en' => 'Service and repair jobs for Rongta scales, with their rates.',
        ],
        'svc.all' => ['ru' => 'Для всех весов', 'uz' => 'Barcha tarozilar uchun', 'en' => 'For all scales'],
        'svc.col.job' => ['ru' => 'Работа', 'uz' => 'Ish', 'en' => 'Job'],
        'svc.col.amount' => ['ru' => 'Тариф', 'uz' => 'Tarif', 'en' => 'Rate'],
        'svc.amount' => ['ru' => '{amount} сум', 'uz' => '{amount} soʻm', 'en' => '{amount} UZS'],
        'svc.amount.from' => ['ru' => 'от {amount} сум', 'uz' => '{amount} soʻmdan', 'en' => 'from {amount} UZS'],
        'svc.updated' => ['ru' => 'Обновлено: {date}', 'uz' => 'Yangilangan: {date}', 'en' => 'Updated: {date}'],
        // Public serial number check: model and warranty period of an imported scale.
        'sn.h' => ['ru' => 'Проверка серийного номера', 'uz' => 'Seriya raqamini tekshirish', 'en' => 'Check a serial number'],
        'sn.nav' => ['ru' => 'Проверить гарантию', 'uz' => 'Kafolatni tekshirish', 'en' => 'Check warranty'],
        'sn.lead' => ['ru' => 'Введите серийный номер весов Rongta: покажем модель и срок гарантии.', 'uz' => 'Rongta tarozisining seriya raqamini kiriting: model va kafolat muddatini koʻrsatamiz.', 'en' => 'Enter the serial number of your Rongta scale to see its model and warranty period.'],
        'sn.label' => ['ru' => 'Серийный номер', 'uz' => 'Seriya raqami', 'en' => 'Serial number'],
        'sn.btn' => ['ru' => 'Проверить', 'uz' => 'Tekshirish', 'en' => 'Check'],
        'sn.err.required' => ['ru' => 'Введите серийный номер.', 'uz' => 'Seriya raqamini kiriting.', 'en' => 'Enter a serial number.'],
        'sn.err.throttle' => ['ru' => 'Слишком много проверок. Попробуйте позже.', 'uz' => 'Juda koʻp tekshiruv. Keyinroq urinib koʻring.', 'en' => 'Too many checks. Please try again later.'],
        'sn.none' => ['ru' => 'Номер {serial} не найден среди весов, которые ввезла RSG. Проверьте номер или обратитесь к дилеру.', 'uz' => '{serial} raqami RSG olib kirgan tarozilar orasida topilmadi. Raqamni tekshiring yoki dilerga murojaat qiling.', 'en' => 'Number {serial} was not found among the scales imported by RSG. Check the number or contact your dealer.'],
        'sn.official' => ['ru' => 'Официальный импорт RSG', 'uz' => 'RSG rasmiy importi', 'en' => 'Official RSG import'],
        'sn.model' => ['ru' => 'Модель', 'uz' => 'Model', 'en' => 'Model'],
        'sn.w.h' => ['ru' => 'Гарантия', 'uz' => 'Kafolat', 'en' => 'Warranty'],
        'sn.w.months' => ['ru' => 'Срок гарантии', 'uz' => 'Kafolat muddati', 'en' => 'Warranty period'],
        'sn.w.months.v' => ['ru' => '{n} мес.', 'uz' => '{n} oy', 'en' => '{n} months'],
        'sn.w.from' => ['ru' => 'Начало гарантии', 'uz' => 'Kafolat boshlanishi', 'en' => 'Warranty start'],
        'sn.w.to' => ['ru' => 'Последний день гарантии', 'uz' => 'Kafolatning oxirgi kuni', 'en' => 'Last day of warranty'],
        'sn.w.active' => ['ru' => 'Гарантия действует', 'uz' => 'Kafolat amal qiladi', 'en' => 'Under warranty'],
        'sn.w.expired' => ['ru' => 'Гарантия закончилась', 'uz' => 'Kafolat muddati tugagan', 'en' => 'Warranty has expired'],
        'sn.w.none' => ['ru' => 'Дата начала гарантии ещё не внесена. Уточните у дилера или в RSG.', 'uz' => 'Kafolat boshlanish sanasi hali kiritilmagan. Dilerdan yoki RSGdan aniqlang.', 'en' => 'The warranty start date is not recorded yet. Ask your dealer or RSG.'],
        'sn.terms' => ['ru' => 'Условия гарантии и сервиса', 'uz' => 'Kafolat va servis shartlari', 'en' => 'Warranty and service terms'],
        // Partner cabinet
        'cab.nav' => ['ru' => 'Кабинет партнёра', 'uz' => 'Hamkor kabineti', 'en' => 'Partner cabinet'],
        'cab.h' => ['ru' => 'Кабинет партнёра', 'uz' => 'Hamkor kabineti', 'en' => 'Partner cabinet'],
        'cab.logout' => ['ru' => 'Выйти', 'uz' => 'Chiqish', 'en' => 'Log out'],
        'cab.noroles' => ['ru' => 'У вашей компании пока не отмечены ни продажа, ни сервис. Обратитесь в RSG.', 'uz' => 'Kompaniyangiz uchun hali na savdo, na servis belgilanmagan. RSGga murojaat qiling.', 'en' => 'Neither sales nor service is set for your company yet. Please contact RSG.'],
        'cab.email' => ['ru' => 'Эл. почта', 'uz' => 'Elektron pochta', 'en' => 'Email'],
        'cab.password' => ['ru' => 'Пароль', 'uz' => 'Parol', 'en' => 'Password'],
        'cab.login.h' => ['ru' => 'Вход в кабинет партнёра', 'uz' => 'Hamkor kabinetiga kirish', 'en' => 'Partner cabinet login'],
        'cab.login.lead' => ['ru' => 'Для дилеров и сервисных центров RSG.', 'uz' => 'RSG dilerlari va servis markazlari uchun.', 'en' => 'For RSG dealers and service centres.'],
        'cab.login.remember' => ['ru' => 'Запомнить меня', 'uz' => 'Meni eslab qol', 'en' => 'Remember me'],
        'cab.login.btn' => ['ru' => 'Войти', 'uz' => 'Kirish', 'en' => 'Log in'],
        'cab.login.no' => ['ru' => 'Нет кабинета?', 'uz' => 'Kabinetingiz yoʻqmi?', 'en' => 'No cabinet yet?'],
        'cab.login.reg' => ['ru' => 'Зарегистрироваться', 'uz' => 'Roʻyxatdan oʻtish', 'en' => 'Register'],
        'cab.login.forgot' => ['ru' => 'Забыли пароль? Обратитесь в RSG: новый пароль задаст администратор.', 'uz' => 'Parolni unutdingizmi? RSGga murojaat qiling: yangi parolni administrator oʻrnatadi.', 'en' => 'Forgot your password? Contact RSG: an administrator will set a new one.'],
        'cab.login.err' => ['ru' => 'Неверная эл. почта или пароль.', 'uz' => 'Elektron pochta yoki parol notoʻgʻri.', 'en' => 'Wrong email or password.'],
        'cab.login.pending' => ['ru' => 'Ваша заявка ещё не одобрена. RSG свяжется с вами после проверки.', 'uz' => 'Arizangiz hali tasdiqlanmagan. Tekshiruvdan soʻng RSG siz bilan bogʻlanadi.', 'en' => 'Your registration is not approved yet. RSG will contact you after checking it.'],
        'cab.login.blocked' => ['ru' => 'Доступ в кабинет закрыт. Обратитесь в RSG.', 'uz' => 'Kabinetga kirish yopilgan. RSGga murojaat qiling.', 'en' => 'Access to the cabinet is closed. Please contact RSG.'],
        'cab.login.throttle' => ['ru' => 'Слишком много попыток. Повторите через {s} с.', 'uz' => 'Urinishlar juda koʻp. {s} soniyadan soʻng qayta urinib koʻring.', 'en' => 'Too many attempts. Try again in {s} s.'],
        'cab.reg.h' => ['ru' => 'Регистрация партнёра', 'uz' => 'Hamkorni roʻyxatdan oʻtkazish', 'en' => 'Partner registration'],
        'cab.reg.lead' => ['ru' => 'Заполните форму. Кабинет откроется после того, как RSG проверит и одобрит заявку.', 'uz' => 'Shaklni toʻldiring. RSG arizani tekshirib, tasdiqlaganidan soʻng kabinet ochiladi.', 'en' => 'Fill in the form. The cabinet opens after RSG checks and approves your registration.'],
        'cab.reg.company' => ['ru' => 'Компания', 'uz' => 'Kompaniya', 'en' => 'Company'],
        'cab.reg.name' => ['ru' => 'Контактное лицо', 'uz' => 'Masʼul shaxs', 'en' => 'Contact person'],
        'cab.reg.phone' => ['ru' => 'Мобильный телефон', 'uz' => 'Mobil telefon', 'en' => 'Mobile phone'],
        'cab.reg.password2' => ['ru' => 'Пароль ещё раз', 'uz' => 'Parolni takrorlang', 'en' => 'Repeat password'],
        'cab.reg.password.hint' => ['ru' => 'Не меньше 8 знаков.', 'uz' => 'Kamida 8 ta belgi.', 'en' => 'At least 8 characters.'],
        'cab.reg.roles' => ['ru' => 'Чем занимается компания', 'uz' => 'Kompaniya nima bilan shugʻullanadi', 'en' => 'What the company does'],
        'cab.reg.sales' => ['ru' => 'Продаёт весы', 'uz' => 'Tarozi sotadi', 'en' => 'Sells scales'],
        'cab.reg.service' => ['ru' => 'Ремонтирует и обслуживает весы', 'uz' => 'Tarozilarni taʼmirlaydi va xizmat koʻrsatadi', 'en' => 'Repairs and services scales'],
        'cab.reg.btn' => ['ru' => 'Отправить заявку', 'uz' => 'Arizani yuborish', 'en' => 'Send registration'],
        'cab.reg.have' => ['ru' => 'Уже есть кабинет?', 'uz' => 'Kabinetingiz bormi?', 'en' => 'Already have a cabinet?'],
        'cab.reg.err.email' => ['ru' => 'Введите эл. почту, например name@company.uz.', 'uz' => 'Elektron pochtani kiriting, masalan name@company.uz.', 'en' => 'Enter an email address, for example name@company.uz.'],
        'cab.reg.err.taken' => ['ru' => 'Эта эл. почта уже зарегистрирована.', 'uz' => 'Bu elektron pochta allaqachon roʻyxatdan oʻtgan.', 'en' => 'This email is already registered.'],
        'cab.reg.err.password' => ['ru' => 'Пароль: не меньше 8 знаков.', 'uz' => 'Parol: kamida 8 ta belgi.', 'en' => 'Password: at least 8 characters.'],
        'cab.reg.err.confirm' => ['ru' => 'Пароли не совпадают.', 'uz' => 'Parollar mos kelmadi.', 'en' => 'The passwords do not match.'],
        'cab.reg.err.roles' => ['ru' => 'Отметьте хотя бы одно.', 'uz' => 'Kamida bittasini belgilang.', 'en' => 'Tick at least one.'],
        'cab.reg.err.throttle' => ['ru' => 'Слишком много заявок. Попробуйте позже.', 'uz' => 'Arizalar juda koʻp. Keyinroq urinib koʻring.', 'en' => 'Too many registrations. Please try again later.'],
        'cab.reg.done.h' => ['ru' => 'Заявка отправлена', 'uz' => 'Ariza yuborildi', 'en' => 'Registration sent'],
        'cab.reg.done' => ['ru' => 'RSG проверит данные и откроет кабинет. После этого вы сможете войти с эл. почтой {email}.', 'uz' => 'RSG maʼlumotlarni tekshirib, kabinetni ochadi. Shundan soʻng {email} elektron pochtasi bilan kira olasiz.', 'en' => 'RSG will check the details and open the cabinet. After that you can log in with {email}.'],
        'cab.sales.h' => ['ru' => 'Мои весы', 'uz' => 'Mening tarozilarim', 'en' => 'My scales'],
        'cab.sales.lead' => ['ru' => 'Весы, которые RSG передала вашей компании.', 'uz' => 'RSG kompaniyangizga topshirgan tarozilar.', 'en' => 'Scales that RSG has handed over to your company.'],
        'cab.sales.search' => ['ru' => 'Поиск по серийному номеру', 'uz' => 'Seriya raqami boʻyicha qidirish', 'en' => 'Search by serial number'],
        'cab.sales.none' => ['ru' => 'Весов пока нет.', 'uz' => 'Hozircha tarozilar yoʻq.', 'en' => 'No scales yet.'],
        'cab.sales.notfound' => ['ru' => 'Ничего не найдено.', 'uz' => 'Hech narsa topilmadi.', 'en' => 'Nothing found.'],
        'cab.col.handed' => ['ru' => 'Передан', 'uz' => 'Topshirilgan', 'en' => 'Handed over'],
        'cab.col.until' => ['ru' => 'Гарантия до', 'uz' => 'Kafolat tugashi', 'en' => 'Warranty until'],
        'cab.col.date' => ['ru' => 'Дата', 'uz' => 'Sana', 'en' => 'Date'],
        'cab.col.event' => ['ru' => 'Событие', 'uz' => 'Hodisa', 'en' => 'Event'],
        'cab.col.details' => ['ru' => 'Подробности', 'uz' => 'Tafsilotlar', 'en' => 'Details'],
        'cab.w.active' => ['ru' => 'Действует', 'uz' => 'Amal qiladi', 'en' => 'Active'],
        'cab.w.expired' => ['ru' => 'Закончилась', 'uz' => 'Tugagan', 'en' => 'Expired'],
        'cab.w.none' => ['ru' => 'Не начата', 'uz' => 'Boshlanmagan', 'en' => 'Not started'],
        'cab.prev' => ['ru' => 'Назад', 'uz' => 'Orqaga', 'en' => 'Back'],
        'cab.next' => ['ru' => 'Дальше', 'uz' => 'Keyingi', 'en' => 'Next'],
        'cab.svc.h' => ['ru' => 'Сервис', 'uz' => 'Servis', 'en' => 'Service'],
        'cab.svc.lead' => ['ru' => 'Найдите весы по серийному номеру, чтобы увидеть гарантию и записать работу.', 'uz' => 'Kafolatni koʻrish va ishni qayd etish uchun tarozini seriya raqami boʻyicha toping.', 'en' => 'Find a scale by its serial number to see its warranty and record your work.'],
        'cab.svc.find' => ['ru' => 'Найти', 'uz' => 'Topish', 'en' => 'Find'],
        'cab.svc.limited' => ['ru' => 'Полная история видна для весов, которые ваш центр принимал в сервис. Для остальных видны только статус и гарантия.', 'uz' => 'Toʻliq tarix markazingiz servisga qabul qilgan tarozilar uchun koʻrinadi. Qolganlari uchun faqat holat va kafolat koʻrinadi.', 'en' => 'The full history is shown for scales your centre has accepted for service. For other scales only the status and warranty are shown.'],
        'cab.svc.history' => ['ru' => 'История', 'uz' => 'Tarix', 'en' => 'History'],
        'cab.svc.err.date' => ['ru' => 'Дата: не раньше импорта весов и не позже сегодняшнего дня.', 'uz' => 'Sana: tarozi import qilingan kundan oldin va bugundan keyin boʻlmasligi kerak.', 'en' => 'Date: not before the scale was imported and not after today.'],
        'cab.svc.add.h' => ['ru' => 'Записать работу', 'uz' => 'Ishni qayd etish', 'en' => 'Record work'],
        'cab.svc.add.case' => ['ru' => 'Работа', 'uz' => 'Ish turi', 'en' => 'Job'],
        'cab.svc.add.case.none' => ['ru' => 'Не указана', 'uz' => 'Koʻrsatilmagan', 'en' => 'Not specified'],
        'cab.svc.add.note' => ['ru' => 'Примечание', 'uz' => 'Izoh', 'en' => 'Note'],
        'cab.svc.add.btn' => ['ru' => 'Записать', 'uz' => 'Qayd etish', 'en' => 'Record'],
        'cab.svc.add.done' => ['ru' => 'Запись добавлена в историю.', 'uz' => 'Yozuv tarixga qoʻshildi.', 'en' => 'The record was added to the history.'],
        'cab.svc.mine.h' => ['ru' => 'Весы, которые вы обслуживали', 'uz' => 'Siz xizmat koʻrsatgan tarozilar', 'en' => 'Scales you have serviced'],
        'cab.svc.open' => ['ru' => 'Открыть', 'uz' => 'Ochish', 'en' => 'Open'],
        'cab.mv.import' => ['ru' => 'Импортирован RSG', 'uz' => 'RSG tomonidan import qilingan', 'en' => 'Imported by RSG'],
        'cab.mv.dealer' => ['ru' => 'Передан дилеру', 'uz' => 'Dilerga topshirilgan', 'en' => 'Handed to a dealer'],
        'cab.mv.service_in' => ['ru' => 'Принят в сервис', 'uz' => 'Servisga qabul qilingan', 'en' => 'Accepted for service'],
        'cab.mv.service_done' => ['ru' => 'Отремонтирован', 'uz' => 'Taʼmirlangan', 'en' => 'Repaired'],
        'cab.mv.service_out' => ['ru' => 'Возвращён владельцу', 'uz' => 'Egasiga qaytarilgan', 'en' => 'Returned to the owner'],
        'cab.req.h' => ['ru' => 'Запросить подробности у RSG', 'uz' => 'RSGdan tafsilotlarni soʻrash', 'en' => 'Ask RSG for details'],
        'cab.req.note' => ['ru' => 'Зачем нужны подробности (необязательно)', 'uz' => 'Tafsilotlar nima uchun kerak (ixtiyoriy)', 'en' => 'Why you need the details (optional)'],
        'cab.req.btn' => ['ru' => 'Отправить запрос', 'uz' => 'Soʻrov yuborish', 'en' => 'Send request'],
        'cab.req.new' => ['ru' => 'Запрос отправлен {date}. Ждём решения RSG.', 'uz' => 'Soʻrov {date} kuni yuborilgan. RSG qarori kutilmoqda.', 'en' => 'Request sent on {date}. Waiting for RSG to decide.'],
        'cab.req.refused' => ['ru' => 'RSG отклонила запрос от {date}.', 'uz' => 'RSG {date} kungi soʻrovni rad etdi.', 'en' => 'RSG declined the request of {date}.'],
        'cab.req.answer' => ['ru' => 'Ответ RSG: {text}', 'uz' => 'RSG javobi: {text}', 'en' => 'RSG answered: {text}'],
        'cab.req.granted' => ['ru' => 'RSG открыла вашему центру историю этих весов.', 'uz' => 'RSG markazingizga ushbu tarozi tarixini ochdi.', 'en' => 'RSG has opened the history of this scale to your centre.'],
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
