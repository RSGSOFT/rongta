<?php

namespace App\Livewire\Admin\Chatbot;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\Setting;
use App\Services\Chatbot\ChatbotSettings;
use App\Services\Chatbot\Claude\ClaudeFactory;
use App\Services\Chatbot\PriceGuard;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** /admin/chatbot/settings: switch, texts, rules, off-limits topics and limits. Admins only. */
class Settings extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    protected bool $adminOnly = true;

    public array $form = [];

    public function mount(): void
    {
        $settings = ChatbotSettings::all();
        $this->form = [
            ...$settings,
            'greeting' => $this->transFill($settings['greeting']),
            'fallback' => $this->transFill($settings['fallback']),
        ];
    }

    protected function rules(): array
    {
        $noPrice = function (string $attribute, mixed $value, Closure $fail) {
            if (PriceGuard::hasAmount((string) $value)) {
                $fail('На сайте нет цен: уберите сумму из поля «:attribute».');
            }
        };

        return [
            'form.enabled' => ['boolean'],
            ...$this->transRules('form.greeting', required: false, max: 1000),
            ...$this->transRules('form.fallback', required: false, max: 1000),
            'form.greeting.*' => [$noPrice],
            'form.fallback.*' => [$noPrice],
            'form.rules_extra' => ['nullable', 'string', 'max:4000', $noPrice],
            'form.off_limits' => ['nullable', 'string', 'max:2000'],
            'form.lead_daily_limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'form.ip_daily_limit' => ['required', 'integer', 'min:1', 'max:5000'],
            'form.max_message_chars' => ['required', 'integer', 'min:50', 'max:4000'],
            'form.history_messages' => ['required', 'integer', 'min:0', 'max:40'],
            'form.max_output_tokens' => ['required', 'integer', 'min:500', 'max:16000'],
            'form.effort' => ['required', Rule::in(ChatbotSettings::EFFORTS)],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            ...$this->transAttributes('form.greeting', 'Приветствие'),
            ...$this->transAttributes('form.fallback', 'Ответ «не могу помочь»'),
            'form.rules_extra' => 'Дополнительные правила',
            'form.off_limits' => 'Запретные темы',
            'form.lead_daily_limit' => 'Сообщений в день от одного посетителя',
            'form.ip_daily_limit' => 'Сообщений в день с одного IP-адреса',
            'form.max_message_chars' => 'Длина сообщения',
            'form.history_messages' => 'Сообщений истории',
            'form.max_output_tokens' => 'Предел длины ответа',
            'form.effort' => 'Глубина обдумывания',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $before = ChatbotSettings::all();
        ChatbotSettings::save([
            'enabled' => (bool) $this->form['enabled'],
            'greeting' => $this->transClean($this->form['greeting']),
            'fallback' => $this->transClean($this->form['fallback']),
            'rules_extra' => trim((string) $this->form['rules_extra']),
            'off_limits' => trim((string) $this->form['off_limits']),
            'lead_daily_limit' => (int) $this->form['lead_daily_limit'],
            'ip_daily_limit' => (int) $this->form['ip_daily_limit'],
            'max_message_chars' => (int) $this->form['max_message_chars'],
            'history_messages' => (int) $this->form['history_messages'],
            'max_output_tokens' => (int) $this->form['max_output_tokens'],
            'effort' => $this->form['effort'],
        ]);

        $after = ChatbotSettings::all();
        $changed = array_filter($after, fn ($value, $key) => $value !== ($before[$key] ?? null), ARRAY_FILTER_USE_BOTH);
        if ($changed) {
            $this->logActivity('updated', Setting::where('key', ChatbotSettings::KEY)->first(), $changed);
        }

        session()->flash('status', 'Настройки бота сохранены.');
    }

    public function render(): View
    {
        return view('livewire.admin.chatbot.settings', [
            'hasKey' => ClaudeFactory::make()->isConfigured(),
            'model' => ClaudeFactory::model(),
            'defaultGreeting' => ui('bot.greeting', [], 'ru'),
            'defaultFallback' => ui('bot.fallback', [], 'ru'),
        ])->layout('components.layouts.admin', ['title' => 'Настройки бота']);
    }
}
