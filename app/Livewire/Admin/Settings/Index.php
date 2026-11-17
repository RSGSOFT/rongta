<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Partners\Options;
use App\Livewire\Pages\Home;
use App\Models\Setting;
use App\Models\UiString;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * /admin/settings: site settings, admins only. Stored shapes are the ones the public site reads:
 *   contacts       {phone, telegram, is_placeholder}
 *   accent         "#RRGGBB";  accent_ink / accent_soft  "#RRGGBB" or null (stylesheet default)
 *   logo           {logo, symbol, wordmark}: paths under public/
 *   home.sections  [{key, visible}, ...] in display order
 *   hero           {title_key, lead_key, contact_key, readout_alt_key}: ui() keys
 */
class Index extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation, WithFileUploads;

    protected bool $adminOnly = true;

    /** Stylesheet defaults (resources/css/app.css), used when a companion colour is left empty. */
    public const DEFAULT_ACCENT = '#FF6400';

    public const DEFAULT_ACCENT_INK = '#B04300';

    public const DEFAULT_ACCENT_SOFT = '#FFF0E6';

    public const ON_ACCENT = '#151A21';

    public const INK = '#151A21';

    public const HEX = '/^#[0-9A-Fa-f]{6}$/';

    /** Logo slots: key => [label, hint, bundled default path under public/]. */
    public const LOGOS = [
        'logo' => ['Логотип целиком', 'Знак и название вместе.', 'images/rongta-logo.png'],
        'symbol' => ['Знак', 'В шапке сайта, в чат-боте и у карточки RSG на странице дилеров.', 'images/rongta-symbol.png'],
        'wordmark' => ['Название (надпись)', 'В шапке сайта рядом со знаком.', 'images/rongta-wordmark.png'],
    ];

    public const LOGO_MIMES = 'png,jpg,jpeg,webp';

    public const LOGO_MAX_KB = 2048;

    public const LOGO_DIR = 'logos';

    public const SECTION_LABELS = [
        'hero' => 'Первый экран (заголовок и весы)',
        'chooser' => 'Подбор «Какие весы вам нужны?»',
        'families' => 'Семейства весов',
        'aurora' => 'AURORA AI (тёмный блок)',
        'trust' => 'Блок доверия',
        'knowledge' => 'База знаний',
        'partners' => 'Партнёрам',
    ];

    public const HERO_KEYS = [
        'title_key' => 'Заголовок',
        'lead_key' => 'Подзаголовок',
        'contact_key' => 'Подпись перед контактами',
        'readout_alt_key' => 'Описание табло весов (для экранных дикторов)',
    ];

    public array $contacts = [];

    public array $colors = [];

    /** Current logo paths (under public/). */
    public array $logo = [];

    public $newLogo = null;

    public $newSymbol = null;

    public $newWordmark = null;

    public array $sections = [];

    public array $hero = [];

    public function mount(): void
    {
        $contacts = (array) setting('contacts', []);
        $this->contacts = [
            'phone' => (string) ($contacts['phone'] ?? ''),
            'telegram' => (string) ($contacts['telegram'] ?? ''),
            'is_placeholder' => (bool) ($contacts['is_placeholder'] ?? false),
        ];

        $this->colors = [
            'accent' => $this->hexOr(setting('accent'), self::DEFAULT_ACCENT),
            'accent_ink' => $this->hexOr(setting('accent_ink'), ''),
            'accent_soft' => $this->hexOr(setting('accent_soft'), ''),
        ];

        $logo = (array) setting('logo', []);
        foreach (self::LOGOS as $slot => [, , $default]) {
            $this->logo[$slot] = is_string($logo[$slot] ?? null) && $logo[$slot] !== '' ? $logo[$slot] : $default;
        }

        $this->sections = self::normaliseSections(setting('home.sections'));

        $hero = (array) setting('hero', []);
        $defaults = ['title_key' => 'hero.h1', 'lead_key' => 'hero.lead', 'contact_key' => 'hero.contact', 'readout_alt_key' => 'hero.readout.alt'];
        foreach ($defaults as $field => $default) {
            $this->hero[$field] = is_string($hero[$field] ?? null) && $hero[$field] !== '' ? $hero[$field] : $default;
        }
    }

    protected function hexOr(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match(self::HEX, $value) ? strtoupper($value) : $fallback;
    }

    /** Every known section exactly once: stored order first, then any section the setting does not list (hidden). */
    public static function normaliseSections(mixed $stored): array
    {
        $out = [];
        foreach (is_array($stored) ? $stored : [] as $section) {
            $key = is_array($section) ? ($section['key'] ?? null) : null;
            if (in_array($key, Home::SECTIONS, true) && ! isset($out[$key])) {
                $out[$key] = ['key' => $key, 'visible' => (bool) ($section['visible'] ?? true)];
            }
        }
        // The home page hides a section the stored list leaves out, and shows everything when there is no list.
        $visible = $out === [];
        foreach (Home::SECTIONS as $key) {
            $out[$key] ??= ['key' => $key, 'visible' => $visible];
        }

        return array_values($out);
    }

    public function moveSection(int $index, int $step): void
    {
        $target = $index + ($step < 0 ? -1 : 1);
        if (! isset($this->sections[$index], $this->sections[$target])) {
            return;
        }
        [$this->sections[$index], $this->sections[$target]] = [$this->sections[$target], $this->sections[$index]];
    }

    /** WCAG contrast ratio of two #RRGGBB colours (1 to 21). */
    public static function contrast(string $a, string $b): float
    {
        $luminance = function (string $hex): float {
            $channels = [];
            foreach ([1, 3, 5] as $offset) {
                $c = hexdec(substr($hex, $offset, 2)) / 255;
                $channels[] = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };

        $la = $luminance($a);
        $lb = $luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** Readability checks for the chosen colours: [[label, ratio, passes], ...]. Empty while a value is not valid hex. */
    public function contrastChecks(): array
    {
        $accent = $this->hexOr($this->colors['accent'] ?? null, '');
        $ink = $this->hexOr($this->colors['accent_ink'] ?? null, '') ?: (trim((string) ($this->colors['accent_ink'] ?? '')) === '' ? self::DEFAULT_ACCENT_INK : '');
        $soft = $this->hexOr($this->colors['accent_soft'] ?? null, '') ?: (trim((string) ($this->colors['accent_soft'] ?? '')) === '' ? self::DEFAULT_ACCENT_SOFT : '');

        $checks = [];
        if ($accent !== '') {
            $checks[] = ['Тёмный текст на кнопке фирменного цвета', self::contrast(self::ON_ACCENT, $accent)];
        }
        if ($ink !== '') {
            $checks[] = ['Ссылки тёмным оттенком на белом фоне', self::contrast($ink, '#FFFFFF')];
        }
        if ($soft !== '') {
            $checks[] = ['Обычный текст на светлом оттенке', self::contrast(self::INK, $soft)];
        }
        if ($ink !== '' && $soft !== '') {
            $checks[] = ['Тёмный оттенок на светлом оттенке', self::contrast($ink, $soft)];
        }

        return array_map(fn (array $c) => [$c[0], round($c[1], 1), $c[1] >= 4.5], $checks);
    }

    protected function uploads(): array
    {
        return ['logo' => $this->newLogo, 'symbol' => $this->newSymbol, 'wordmark' => $this->newWordmark];
    }

    protected function logoRule(): array
    {
        return ['nullable', 'file', 'image', 'mimes:'.self::LOGO_MIMES, 'max:'.self::LOGO_MAX_KB];
    }

    public function updated(string $name): void
    {
        // A file that fails the checks is dropped at once, so it is never previewed or saved.
        if (in_array($name, ['newLogo', 'newSymbol', 'newWordmark'], true)) {
            try {
                $this->validateOnly($name);
            } catch (ValidationException $e) {
                $this->{$name} = null;

                throw $e;
            }
        }
    }

    public function clearUpload(string $slot): void
    {
        $property = ['logo' => 'newLogo', 'symbol' => 'newSymbol', 'wordmark' => 'newWordmark'][$slot] ?? null;
        if ($property) {
            $this->{$property} = null;
            $this->resetErrorBag($property);
        }
    }

    /** Back to the bundled file of this slot (applied on save). */
    public function useDefaultLogo(string $slot): void
    {
        if (isset(self::LOGOS[$slot])) {
            $this->clearUpload($slot);
            $this->logo[$slot] = self::LOGOS[$slot][2];
        }
    }

    protected function rules(): array
    {
        return [
            'contacts.phone' => [Rule::requiredIf(! ($this->contacts['is_placeholder'] ?? false)), 'nullable', 'string', 'max:30', 'regex:'.Options::PHONE_REGEX],
            'contacts.telegram' => ['nullable', 'string', 'max:40', 'regex:'.Options::TELEGRAM_REGEX],
            'contacts.is_placeholder' => ['boolean'],

            'colors.accent' => ['required', 'string', 'regex:'.self::HEX],
            'colors.accent_ink' => ['nullable', 'string', 'regex:'.self::HEX],
            'colors.accent_soft' => ['nullable', 'string', 'regex:'.self::HEX],

            'newLogo' => $this->logoRule(),
            'newSymbol' => $this->logoRule(),
            'newWordmark' => $this->logoRule(),

            'sections' => ['array', 'size:'.count(Home::SECTIONS)],
            'sections.*.key' => ['required', 'distinct', Rule::in(Home::SECTIONS)],
            'sections.*.visible' => ['boolean'],

            'hero.title_key' => ['required', 'string', 'max:120', Rule::exists('ui_strings', 'key')],
            'hero.lead_key' => ['required', 'string', 'max:120', Rule::exists('ui_strings', 'key')],
            'hero.contact_key' => ['required', 'string', 'max:120', Rule::exists('ui_strings', 'key')],
            'hero.readout_alt_key' => ['required', 'string', 'max:120', Rule::exists('ui_strings', 'key')],
        ];
    }

    protected function messages(): array
    {
        $hex = 'Цвет записывается как # и шесть знаков 0–9, A–F, например #FF6400.';
        $key = 'Текста с таким ключом нет. Ключ можно найти в разделе «Тексты интерфейса».';

        return [
            ...$this->russianMessages(),
            'contacts.phone.required' => 'Укажите телефон или включите «Контакты ещё не подтверждены».',
            'contacts.phone.regex' => 'Телефон: только цифры, пробелы, скобки и дефис, можно с «+» в начале. Например +998 71 200 00 00.',
            'contacts.telegram.regex' => 'Telegram: имя пользователя латиницей, например @rongta_uz.',
            'colors.accent.regex' => $hex,
            'colors.accent_ink.regex' => $hex,
            'colors.accent_soft.regex' => $hex,
            'sections.size' => 'Список блоков главной страницы повреждён. Обновите страницу и попробуйте снова.',
            'hero.title_key.exists' => $key,
            'hero.lead_key.exists' => $key,
            'hero.contact_key.exists' => $key,
            'hero.readout_alt_key.exists' => $key,
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'contacts.phone' => 'Телефон',
            'contacts.telegram' => 'Telegram',
            'colors.accent' => 'Фирменный цвет',
            'colors.accent_ink' => 'Тёмный оттенок',
            'colors.accent_soft' => 'Светлый оттенок',
            'newLogo' => 'Логотип целиком',
            'newSymbol' => 'Знак',
            'newWordmark' => 'Название',
            'hero.title_key' => 'Заголовок',
            'hero.lead_key' => 'Подзаголовок',
            'hero.contact_key' => 'Подпись перед контактами',
            'hero.readout_alt_key' => 'Описание табло',
        ];
    }

    public function save(): void
    {
        foreach (['phone', 'telegram'] as $field) {
            $this->contacts[$field] = trim((string) ($this->contacts[$field] ?? ''));
        }
        foreach (['accent', 'accent_ink', 'accent_soft'] as $field) {
            $this->colors[$field] = strtoupper(trim((string) ($this->colors[$field] ?? '')));
        }
        foreach (array_keys(self::HERO_KEYS) as $field) {
            $this->hero[$field] = trim((string) ($this->hero[$field] ?? ''));
        }
        $this->sections = array_values($this->sections);

        $this->validate();

        if (! collect($this->sections)->contains(fn ($section) => ! empty($section['visible']))) {
            throw ValidationException::withMessages(['sections' => 'Оставьте включённым хотя бы один блок: иначе главная страница будет пустой.']);
        }

        $disk = Storage::disk('public');
        $oldLogo = (array) setting('logo', []);

        // Logo paths: only a bundled default or a file this screen stored is accepted.
        $logo = $oldLogo;
        $stored = [];
        foreach (self::LOGOS as $slot => [, , $default]) {
            $current = (string) ($this->logo[$slot] ?? $default);
            $keep = (string) ($oldLogo[$slot] ?? $default);
            $logo[$slot] = in_array($current, [$default, $keep], true) ? $current : $keep;

            if ($file = $this->uploads()[$slot]) {
                // From the file's content, never from the name the browser sent.
                $extension = Str::lower((string) $file->extension()) ?: 'png';
                $path = $file->storeAs(self::LOGO_DIR, $slot.'-'.Str::lower(Str::random(10)).'.'.$extension, 'public');
                $logo[$slot] = 'storage/'.$path;
                $stored[] = $path;
            }
        }

        $values = [
            'contacts' => array_merge((array) setting('contacts', []), [
                'phone' => $this->contacts['phone'],
                'telegram' => $this->contacts['telegram'] !== '' ? '@'.ltrim($this->contacts['telegram'], '@') : '',
                'is_placeholder' => (bool) $this->contacts['is_placeholder'],
            ]),
            'accent' => $this->colors['accent'],
            'accent_ink' => $this->colors['accent_ink'] !== '' ? $this->colors['accent_ink'] : null,
            'accent_soft' => $this->colors['accent_soft'] !== '' ? $this->colors['accent_soft'] : null,
            'logo' => $logo,
            'home.sections' => array_map(fn (array $s) => ['key' => $s['key'], 'visible' => (bool) ($s['visible'] ?? false)], $this->sections),
            'hero' => array_merge((array) setting('hero', []), $this->hero),
        ];

        try {
            $changed = 0;
            foreach ($values as $key => $value) {
                $row = Setting::firstOrNew(['key' => $key]);
                if ($row->exists && self::canonical($row->value) === self::canonical($value)) {
                    continue;
                }
                $row->value = $value;
                $row->save();
                $this->logActivity($row->wasRecentlyCreated ? 'created' : 'updated', $row, ['key' => $key, 'value' => $value]);
                $changed++;
            }
        } catch (\Throwable $e) {
            $disk->delete($stored);

            throw $e;
        }
        Setting::flush();

        // Replaced uploads are removed; bundled files under public/images are never touched.
        foreach (self::LOGOS as $slot => $definition) {
            $old = (string) ($oldLogo[$slot] ?? '');
            if ($old !== $logo[$slot] && str_starts_with($old, 'storage/'.self::LOGO_DIR.'/')) {
                $disk->delete(substr($old, strlen('storage/')));
            }
        }

        $this->logo = array_intersect_key($logo, self::LOGOS);
        $this->contacts['telegram'] = $values['contacts']['telegram'];
        $this->reset('newLogo', 'newSymbol', 'newWordmark');

        session()->flash('status', $changed ? 'Настройки сохранены.' : 'Изменений нет: настройки уже такие.');
    }

    /** Same value regardless of object key order (MySQL JSON columns reorder keys). */
    protected static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $value = array_map([self::class, 'canonical'], $value);
        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    public function render(): View
    {
        $heroTexts = UiString::whereIn('key', array_values($this->hero))->get()->keyBy('key');

        return view('livewire.admin.settings.index', [
            'checks' => $this->contrastChecks(),
            'uploads' => $this->uploads(),
            'heroTexts' => $heroTexts,
        ])->layout('components.layouts.admin', ['title' => 'Настройки сайта']);
    }
}
