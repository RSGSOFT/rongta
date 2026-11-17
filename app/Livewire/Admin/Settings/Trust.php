<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Items\Catalog;
use App\Models\Setting;
use App\Support\Trust as TrustMaterial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * /admin/settings/trust: the material behind the public trust block ("Доверие"), admins only.
 * Stored in the "trust" setting (shape in App\Support\Trust); files go to the public disk under
 * trust/, named from their content, never from the name the browser sent. Nothing is shown on
 * the site for an item until it has material here.
 */
class Trust extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation, WithFileUploads;

    protected bool $adminOnly = true;

    public const DIR = 'trust';

    /** Image files: same types as the logo upload. */
    public const IMAGE_MIMES = 'png,jpg,jpeg,webp';

    /** Logos keep the logo limit; photos and the certificate scan the catalog image limit. */
    public const LOGO_MAX_KB = Index::LOGO_MAX_KB;

    public const IMAGE_MAX_KB = Catalog::IMAGE_MAX_KB;

    /** Documents (certificate PDF, metrology files): the document limit. */
    public const DOC_MAX_KB = Catalog::FILE_MAX_KB;

    public const CERT_MIMES = 'png,jpg,jpeg,webp,pdf';

    public const METROLOGY_MIMES = 'pdf,jpg,jpeg,png,webp';

    /** list => [title, hint, upload property, text field] */
    public const LISTS = [
        'clients' => ['Логотипы клиентов', 'Магазины и сети, которые работают на весах Rongta. Название нужно для экранных дикторов.', 'newClients', 'name'],
        'pos' => ['Логотипы кассовых программ', 'Совместимые кассовые программы. Показываются в блоке доверия и на странице каждой модели («Работает с программами»).', 'newPos', 'name'],
        'metrology' => ['Документы по метрологии', 'Документы для применения весов в торговле в Узбекистане. Название на трёх языках.', 'newMetrology', 'title'],
        'photos' => ['Фото из магазинов', 'Весы Rongta в магазинах Узбекистана. Подпись на трёх языках (необязательно).', 'newPhotos', 'caption'],
    ];

    /** File name prefix of each list under trust/. */
    public const PREFIXES = ['clients' => 'client', 'pos' => 'pos', 'metrology' => 'metrology', 'photos' => 'photo'];

    /** Current certificate {path, kind} or null (applied on save). */
    public ?array $certificate = null;

    public $newCertificate = null;

    public array $service = [];

    /** Stored entries per list, editable; removals apply on save. */
    public array $items = [];

    /** Files chosen but not saved yet, per list: [{file, name|title|caption}]. */
    public array $pending = ['clients' => [], 'pos' => [], 'metrology' => [], 'photos' => []];

    public array $newClients = [];

    public array $newPos = [];

    public array $newMetrology = [];

    public array $newPhotos = [];

    public function mount(): void
    {
        $trust = TrustMaterial::all();
        $this->certificate = $trust['certificate'];
        $this->service = ['text' => $this->transFill($trust['service']['text']), 'address' => $this->transFill($trust['service']['address'])];
        foreach (array_keys(self::LISTS) as $list) {
            $this->items[$list] = $trust[$list];
        }
    }

    /** Validation rules of one uploaded file of a list (or of the certificate). */
    protected function fileRules(string $list): array
    {
        return match ($list) {
            'clients', 'pos' => ['file', 'image', 'mimes:'.self::IMAGE_MIMES, 'max:'.self::LOGO_MAX_KB],
            'photos' => ['file', 'image', 'mimes:'.self::IMAGE_MIMES, 'max:'.self::IMAGE_MAX_KB],
            'metrology' => ['file', 'mimes:'.self::METROLOGY_MIMES, 'max:'.self::DOC_MAX_KB],
            'certificate' => ['file', 'mimes:'.self::CERT_MIMES, 'max:'.self::DOC_MAX_KB, function ($attribute, $file, $fail) {
                // A scan saved as an image keeps the image limit; a PDF may be as large as any document.
                if ($file instanceof UploadedFile && self::extensionOf($file) !== 'pdf' && $file->getSize() > self::IMAGE_MAX_KB * 1024) {
                    $fail('Изображение сертификата: не больше '.intdiv(self::IMAGE_MAX_KB, 1024).' МБ. Большой скан загрузите в PDF.');
                }
            }],
        };
    }

    public static function extensionOf(UploadedFile $file): string
    {
        // From the file's content, never from the name the browser sent.
        $extension = Str::lower((string) $file->extension());

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    public function updated(string $name): void
    {
        if ($name === 'newCertificate') {
            try {
                $this->validateOnly('newCertificate', ['newCertificate' => ['nullable', ...$this->fileRules('certificate')]]);
            } catch (ValidationException $e) {
                $this->newCertificate = null;

                throw $e;
            }

            return;
        }

        foreach (self::LISTS as $list => [, , $property, $field]) {
            if ($name !== $property && ! str_starts_with($name, $property.'.')) {
                continue;
            }
            // Every chosen file is checked at once; a file that fails is dropped and never stored.
            $files = array_values(array_filter((array) $this->{$property}));
            $this->{$property} = [];
            $failed = [];
            foreach ($files as $file) {
                $check = validator(['file' => $file], ['file' => $this->fileRules($list)], $this->russianMessages(), ['file' => 'Файл']);
                if ($check->fails()) {
                    $failed[] = ($file instanceof UploadedFile ? $file->getClientOriginalName().': ' : '').$check->errors()->first('file');

                    continue;
                }
                $this->pending[$list][] = ['file' => $file, $field => $field === 'name' ? '' : $this->transEmpty()];
            }
            if ($failed) {
                throw ValidationException::withMessages([$property => implode(' ', $failed)]);
            }

            return;
        }
    }

    public function removeCertificate(): void
    {
        $this->certificate = null;
        $this->newCertificate = null;
        $this->resetErrorBag('newCertificate');
    }

    public function remove(string $list, int $index): void
    {
        if (isset($this->items[$list][$index])) {
            unset($this->items[$list][$index]);
            $this->items[$list] = array_values($this->items[$list]);
        }
    }

    public function removePending(string $list, int $index): void
    {
        if (isset($this->pending[$list][$index])) {
            unset($this->pending[$list][$index]);
            $this->pending[$list] = array_values($this->pending[$list]);
        }
    }

    public function move(string $list, int $index, int $step): void
    {
        $target = $index + ($step < 0 ? -1 : 1);
        if (isset($this->items[$list][$index], $this->items[$list][$target])) {
            [$this->items[$list][$index], $this->items[$list][$target]] = [$this->items[$list][$target], $this->items[$list][$index]];
        }
    }

    protected function rules(): array
    {
        $rules = [
            'newCertificate' => ['nullable', ...$this->fileRules('certificate')],
            ...$this->transRules('service.text', required: false, max: 1000),
            ...$this->transRules('service.address', required: false, max: 300),
        ];
        foreach (['items', 'pending'] as $group) {
            foreach (['clients', 'pos'] as $list) {
                $rules[$group.'.'.$list.'.*.name'] = ['required', 'string', 'max:120'];
            }
            $rules = [
                ...$rules,
                ...$this->transRules($group.'.metrology.*.title', required: true, max: 200),
                ...$this->transRules($group.'.photos.*.caption', required: false, max: 200),
            ];
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return [
            'newCertificate' => 'Сертификат',
            ...$this->transAttributes('service.text', 'Гарантия и сервис'),
            ...$this->transAttributes('service.address', 'Адрес сервисного центра'),
            'items.clients.*.name' => 'Название клиента',
            'pending.clients.*.name' => 'Название клиента',
            'items.pos.*.name' => 'Название программы',
            'pending.pos.*.name' => 'Название программы',
            ...$this->transAttributes('items.metrology.*.title', 'Название документа'),
            ...$this->transAttributes('pending.metrology.*.title', 'Название документа'),
            ...$this->transAttributes('items.photos.*.caption', 'Подпись к фото'),
            ...$this->transAttributes('pending.photos.*.caption', 'Подпись к фото'),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $disk = Storage::disk('public');
        $old = TrustMaterial::all();
        $stored = [];
        $store = function (UploadedFile $file, string $prefix) use (&$stored): string {
            $path = $file->storeAs(self::DIR, $prefix.'-'.Str::lower(Str::random(10)).'.'.self::extensionOf($file), 'public');
            $stored[] = $path;

            return 'storage/'.$path;
        };

        try {
            // Only paths this screen stored before are kept: nothing from the browser is trusted as a path.
            $known = collect([$old['certificate']['path'] ?? null])
                ->merge(collect(array_keys(self::LISTS))->flatMap(fn ($l) => array_column($old[$l], 'path')))
                ->filter()->all();
            $keep = fn ($path) => is_string($path) && in_array($path, $known, true);

            $certificate = $this->certificate && $keep($this->certificate['path'] ?? null) ? $this->certificate : null;
            if ($this->newCertificate) {
                $extension = self::extensionOf($this->newCertificate);
                $certificate = ['path' => $store($this->newCertificate, 'certificate'), 'kind' => $extension === 'pdf' ? 'pdf' : 'image'];
            }

            $value = [
                'certificate' => $certificate,
                'service' => ['text' => $this->transClean($this->service['text'] ?? []), 'address' => $this->transClean($this->service['address'] ?? [])],
            ];
            foreach (self::LISTS as $list => [, , , $field]) {
                $entries = [];
                foreach ($this->items[$list] ?? [] as $item) {
                    if ($keep($item['path'] ?? null)) {
                        $entries[] = $this->entry($list, $item['path'], $item);
                    }
                }
                foreach ($this->pending[$list] as $item) {
                    if (($item['file'] ?? null) instanceof UploadedFile) {
                        $entries[] = $this->entry($list, $store($item['file'], self::PREFIXES[$list]), $item);
                    }
                }
                $value[$list] = $entries;
            }
            $value = TrustMaterial::normalise($value);

            $row = Setting::firstOrNew(['key' => TrustMaterial::KEY]);
            $changed = ! $row->exists || $row->value != $value;
            if ($changed) {
                $row->value = $value;
                $row->save();
                $this->logActivity($row->wasRecentlyCreated ? 'created' : 'updated', $row, ['key' => TrustMaterial::KEY, 'value' => $value]);
            }
        } catch (\Throwable $e) {
            $disk->delete($stored);

            throw $e;
        }
        Setting::flush();

        // Files that are no longer used are removed.
        $now = collect([$value['certificate']['path'] ?? null])
            ->merge(collect(array_keys(self::LISTS))->flatMap(fn ($l) => array_column($value[$l], 'path')))
            ->filter()->all();
        foreach (array_diff($known, $now) as $path) {
            if (str_starts_with($path, 'storage/'.self::DIR.'/')) {
                $disk->delete(substr($path, strlen('storage/')));
            }
        }

        $this->certificate = $value['certificate'];
        foreach (array_keys(self::LISTS) as $list) {
            $this->items[$list] = $value[$list];
        }
        $this->pending = ['clients' => [], 'pos' => [], 'metrology' => [], 'photos' => []];
        $this->reset('newCertificate', 'newClients', 'newPos', 'newMetrology', 'newPhotos');

        session()->flash('status', $changed ? 'Материалы блока доверия сохранены.' : 'Изменений нет.');
    }

    /** One stored entry of a list, from the edited row. */
    protected function entry(string $list, string $path, array $item): array
    {
        return match ($list) {
            'clients', 'pos' => ['path' => $path, 'name' => trim((string) ($item['name'] ?? ''))],
            'metrology' => ['path' => $path, 'ext' => Str::lower(pathinfo($path, PATHINFO_EXTENSION)), 'title' => $this->transClean($item['title'] ?? [])],
            'photos' => ['path' => $path, 'caption' => $this->transClean($item['caption'] ?? [])],
        };
    }

    public function render(): View
    {
        return view('livewire.admin.settings.trust')
            ->layout('components.layouts.admin', ['title' => 'Настройки сайта: доверие']);
    }
}
