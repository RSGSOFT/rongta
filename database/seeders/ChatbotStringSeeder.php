<?php

namespace Database\Seeders;

use App\Models\UiString;
use App\Support\UiStrings;
use Illuminate\Database\Seeder;

/**
 * Interface strings of the chatbot widget (keys "bot.*"). Additive: existing rows are kept,
 * so text edited in the admin panel is never overwritten.
 * Russian and Uzbek wording still needs a native-speaker review.
 */
class ChatbotStringSeeder extends Seeder
{
    public const STRINGS = [
        'bot.sub' => [
            'Помощник по весам Rongta',
            'Rongta tarozilari boʻyicha yordamchi',
            'Rongta scales assistant',
        ],
        'bot.greeting' => [
            'Здравствуйте! Я помощник Rongta Uzbekistan: отвечаю на вопросы о торговых весах Rongta, их выборе, настройке и подключении. Прежде чем отвечать, прошу оставить имя, фамилию и мобильный номер: по ним менеджер RSG сможет связаться с вами и довести вопрос до конца.',
            'Assalomu alaykum! Men Rongta Uzbekistan yordamchisiman: Rongta savdo tarozilari, ularni tanlash, sozlash va ulash boʻyicha savollarga javob beraman. Javob berishdan oldin ism, familiya va mobil raqamingizni qoldirishingizni soʻrayman: shu maʼlumotlar orqali RSG menejeri siz bilan bogʻlanib, savolingizni oxirigacha hal qiladi.',
            'Hello! I am the Rongta Uzbekistan assistant: I answer questions about Rongta commercial scales, choosing one, setting it up and connecting it. Before I answer, please leave your first name, surname and mobile number, so that an RSG manager can contact you and see your question through.',
        ],
        'bot.gate.note' => [
            'Эти данные получит RSG, официальный дистрибьютор Rongta в Узбекистане, чтобы связаться с вами.',
            'Bu maʼlumotlarni siz bilan bogʻlanish uchun Rongta kompaniyasining Oʻzbekistondagi rasmiy distribyutori RSG oladi.',
            'These details go to RSG, the official Rongta distributor in Uzbekistan, so that they can contact you.',
        ],
        'bot.ai.note' => [
            'Отвечает ИИ-помощник. Важные детали уточняйте у дилера.',
            'Javoblarni sunʼiy intellekt yordamchisi beradi. Muhim tafsilotlarni dilerdan aniqlang.',
            'Answers come from an AI assistant. Confirm important details with your dealer.',
        ],
        'bot.fallback' => [
            'К сожалению, на этот вопрос я ответить не могу. Обратитесь к дилеру в вашем регионе на странице «Дилеры» или в RSG, официальному дистрибьютору Rongta.',
            'Afsuski, bu savolga javob bera olmayman. «Dilerlar» sahifasidagi hududingiz dileriga yoki Rongta rasmiy distribyutori RSG ga murojaat qiling.',
            'Sorry, I cannot answer that question. Please contact a dealer in your region on the Dealers page, or RSG, the official Rongta distributor.',
        ],
        'bot.unavailable' => [
            'Помощник сейчас недоступен. Пожалуйста, обратитесь к дилеру в вашем регионе на странице «Дилеры» или в RSG. Ваши контакты сохранены, менеджер RSG свяжется с вами.',
            'Yordamchi hozir ishlamayapti. Iltimos, «Dilerlar» sahifasidagi hududingiz dileriga yoki RSG ga murojaat qiling. Aloqa maʼlumotlaringiz saqlandi, RSG menejeri siz bilan bogʻlanadi.',
            'The assistant is unavailable right now. Please contact a dealer in your region on the Dealers page, or RSG. Your contact details are saved and an RSG manager will get in touch.',
        ],
        'bot.dealers.link' => [
            'Открыть страницу «Дилеры»',
            '«Dilerlar» sahifasini ochish',
            'Open the Dealers page',
        ],
        'bot.err.long' => [
            'Сообщение слишком длинное. Сократите его до {max} символов.',
            'Xabar juda uzun. Uni {max} ta belgigacha qisqartiring.',
            'The message is too long. Please shorten it to {max} characters.',
        ],
        'bot.err.limit' => [
            'На сегодня лимит сообщений исчерпан. Обратитесь к дилеру на странице «Дилеры» или в RSG.',
            'Bugungi xabarlar limiti tugadi. «Dilerlar» sahifasidagi dilerga yoki RSG ga murojaat qiling.',
            'You have reached today\'s message limit. Please contact a dealer on the Dealers page, or RSG.',
        ],
        'bot.err.gate' => [
            'Слишком много попыток. Попробуйте ещё раз через несколько минут.',
            'Urinishlar juda koʻp. Bir necha daqiqadan soʻng qayta urinib koʻring.',
            'Too many attempts. Please try again in a few minutes.',
        ],
    ];

    public function run(): void
    {
        foreach (self::STRINGS as $key => [$ru, $uz, $en]) {
            UiString::firstOrCreate(['key' => $key], ['ru' => $ru, 'uz' => $uz, 'en' => $en]);
        }

        UiStrings::flush();
    }
}
