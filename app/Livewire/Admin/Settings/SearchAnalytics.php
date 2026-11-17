<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\Setting;
use App\Support\SearchTools;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * /admin/settings/search: Google and Yandex verification codes, Google Analytics 4 and
 * Yandex Metrica ids, and IndexNow, admins only. Stored in the "search" setting
 * (shape and output in App\Support\SearchTools). Empty fields switch the tool off.
 */
class SearchAnalytics extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation;

    protected bool $adminOnly = true;

    public array $form = [];

    public function mount(): void
    {
        $settings = SearchTools::all();
        $this->form = [
            'google_verification' => $settings['google_verification'],
            'yandex_verification' => $settings['yandex_verification'],
            'ga4_id' => $settings['ga4_id'],
            'metrica_id' => $settings['metrica_id'],
            'indexnow' => $settings['indexnow'],
        ];
    }

    protected function rules(): array
    {
        return [
            'form.google_verification' => ['nullable', 'string', 'regex:'.SearchTools::GOOGLE_PATTERN],
            'form.yandex_verification' => ['nullable', 'string', 'regex:'.SearchTools::YANDEX_PATTERN],
            'form.ga4_id' => ['nullable', 'string', 'regex:'.SearchTools::GA4_PATTERN],
            'form.metrica_id' => ['nullable', 'string', 'regex:'.SearchTools::METRICA_PATTERN],
            'form.indexnow' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            ...$this->russianMessages(),
            'form.google_verification.regex' => 'Код Google: строка из латинских букв, цифр, «-» и «_» (значение content из мета-тега google-site-verification).',
            'form.yandex_verification.regex' => 'Код Яндекса: строка из цифр и букв a–f (значение content из мета-тега yandex-verification).',
            'form.ga4_id.regex' => 'Идентификатор Google Analytics 4 выглядит так: G-XXXXXXXXXX.',
            'form.metrica_id.regex' => 'Номер счётчика Яндекс Метрики: только цифры, например 98765432.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.google_verification' => 'Код подтверждения Google',
            'form.yandex_verification' => 'Код подтверждения Яндекса',
            'form.ga4_id' => 'Google Analytics 4',
            'form.metrica_id' => 'Яндекс Метрика',
        ];
    }

    public function save(): void
    {
        // A pasted meta tag is reduced to its code; ids are tidied before checking.
        $this->form['google_verification'] = SearchTools::codeFrom((string) $this->form['google_verification']);
        $this->form['yandex_verification'] = SearchTools::codeFrom((string) $this->form['yandex_verification']);
        $this->form['ga4_id'] = strtoupper(trim((string) $this->form['ga4_id']));
        $this->form['metrica_id'] = trim((string) $this->form['metrica_id']);
        $this->validate();

        $current = SearchTools::all();
        $value = [
            'google_verification' => $this->form['google_verification'],
            'yandex_verification' => $this->form['yandex_verification'],
            'ga4_id' => $this->form['ga4_id'],
            'metrica_id' => $this->form['metrica_id'],
            'indexnow' => (bool) $this->form['indexnow'],
            // The key is made once and kept, so engines that already know it keep trusting our pings.
            'indexnow_key' => $current['indexnow_key'] !== '' ? $current['indexnow_key'] : bin2hex(random_bytes(16)),
        ];

        $row = Setting::firstOrNew(['key' => SearchTools::KEY]);
        $changed = ! $row->exists || $row->value != $value;
        if ($changed) {
            $row->value = $value;
            $row->save();
            $this->logActivity($row->wasRecentlyCreated ? 'created' : 'updated', $row, ['key' => SearchTools::KEY] + array_diff_key($value, ['indexnow_key' => true]));
        }
        Setting::flush();

        session()->flash('status', $changed ? 'Настройки поиска и аналитики сохранены.' : 'Изменений нет.');
    }

    public function render(): View
    {
        $settings = SearchTools::all();

        return view('livewire.admin.settings.search-analytics', [
            'keyUrl' => $settings['indexnow'] && $settings['indexnow_key'] !== '' ? route('indexnow-key') : null,
            'live' => SearchTools::trackingEnabled(),
            'sitemapUrl' => route('sitemap'),
        ])->layout('components.layouts.admin', ['title' => 'Настройки сайта: поиск и аналитика']);
    }
}
