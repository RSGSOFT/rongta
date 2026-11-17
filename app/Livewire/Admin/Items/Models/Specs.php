<?php

namespace App\Livewire\Admin\Items\Models;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Items\Catalog;
use App\Models\ModelSpec;
use App\Models\ScaleModel;
use App\Models\UiString;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Specs of one model (/admin/models/{model}/specs), grouped by ModelSpec::GROUPS.
 *
 * Each row ($rows[key]) is one spec key:
 *   on     the spec is stored for this model (off = no row, nothing shown on the site)
 *   tbc    value is the standard "to be confirmed" text (ui string val.tbc)
 *   shape  text | note | parts, the three stored value shapes (see ModelSpec)
 *   text   {ru,uz,en}   the value (shapes text and note)
 *   note   {ru,uz,en}   the caveat (shape note)
 *   parts  [[label {ru,uz,en}, value {ru,uz,en}], ...]  e.g. the 15 kg / 30 kg versions (shape parts)
 */
class Specs extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    public const SHAPES = [
        'text' => 'Одно значение',
        'note' => 'Значение с примечанием',
        'parts' => 'По версиям (несколько значений)',
    ];

    #[Locked]
    public int $modelId;

    /** key => group, in display order (changed only through actions). */
    #[Locked]
    public array $keys = [];

    public array $rows = [];

    /** New custom key per group: group => key. */
    public array $newKey = [];

    public function mount(ScaleModel $model): void
    {
        $this->modelId = $model->id;
        $specs = $model->specs()->get()->keyBy('key');

        // Known keys first, in catalogue order; then keys this model has that the catalogue does not list.
        $keys = [];
        foreach (ModelSpec::GROUPS as $group) {
            foreach (Catalog::SPEC_KEYS[$group] ?? [] as $key) {
                $keys[$key] = $group;
            }
        }
        foreach ($specs as $key => $spec) {
            $keys[$key] ??= $spec->group;
        }
        $this->keys = $keys;

        foreach ($keys as $key => $group) {
            $this->rows[$key] = $this->rowFrom($specs[$key] ?? null);
        }
    }

    private function rowFrom(?ModelSpec $spec): array
    {
        $row = [
            'on' => false,
            'tbc' => false,
            'shape' => 'text',
            'text' => $this->transEmpty(),
            'note' => $this->transEmpty(),
            'parts' => [],
        ];
        if (! $spec) {
            return $row;
        }

        $value = $spec->value ?? [];
        $row['on'] = true;
        $row['shape'] = $spec->shape();

        if ($row['shape'] === 'parts') {
            foreach ($value['parts'] ?? [] as $part) {
                $row['parts'][] = ['label' => $this->transFill($part[0] ?? null), 'value' => $this->transFill($part[1] ?? null)];
            }
        } elseif ($row['shape'] === 'note') {
            $row['text'] = $this->transFill($value['v'] ?? null);
            $row['note'] = $this->transFill($value['note'] ?? null);
        } else {
            $row['text'] = $this->transFill($value);
            if ($row['text'] === $this->tbcValue()) {
                $row['tbc'] = true;
                $row['text'] = $this->transEmpty();
            }
        }

        return $row;
    }

    /** The standard "to be confirmed" value in three languages (ui string val.tbc). */
    protected function tbcValue(): array
    {
        $string = UiString::where('key', 'val.tbc')->first();

        return [
            'ru' => trim((string) $string?->ru) ?: 'уточняется',
            'uz' => trim((string) $string?->uz) ?: 'aniqlanmoqda',
            'en' => trim((string) $string?->en) ?: 'to be confirmed',
        ];
    }

    /** Group order: ModelSpec::GROUPS, then any other group found on this model. */
    protected function groups(): array
    {
        return array_values(array_unique([...ModelSpec::GROUPS, ...array_values($this->keys)]));
    }

    public function addPart(string $key): void
    {
        if (! isset($this->rows[$key]) || count($this->rows[$key]['parts']) >= 6) {
            return;
        }
        $this->rows[$key]['parts'][] = ['label' => $this->transEmpty(), 'value' => $this->transEmpty()];
    }

    public function removePart(string $key, int $index): void
    {
        if (isset($this->rows[$key]['parts'][$index])) {
            unset($this->rows[$key]['parts'][$index]);
            $this->rows[$key]['parts'] = array_values($this->rows[$key]['parts']);
            $this->resetErrorBag();
        }
    }

    /** A row switched to "parts" starts with the two usual versions to fill in. */
    public function updatedRows($value, string $path): void
    {
        [$key, $field] = array_pad(explode('.', $path), 2, null);
        if ($field === 'shape' && $value === 'parts' && isset($this->rows[$key]) && $this->rows[$key]['parts'] === []) {
            $this->addPart($key);
            $this->addPart($key);
        }
    }

    public function addKey(string $group): void
    {
        if (! in_array($group, $this->groups(), true)) {
            return;
        }

        $key = trim((string) ($this->newKey[$group] ?? ''));
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9]{1,39}$/', $key)) {
            throw ValidationException::withMessages(["newKey.{$group}" => 'Ключ: латинские буквы и цифры, начинается с буквы, например «loadCell».']);
        }
        if (isset($this->keys[$key])) {
            throw ValidationException::withMessages(["newKey.{$group}" => 'Такой ключ уже есть в списке.']);
        }

        $this->resetErrorBag("newKey.{$group}");
        $this->keys[$key] = $group;
        $this->rows[$key] = ['on' => true] + $this->rowFrom(null);
        $this->newKey[$group] = '';
    }

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->keys as $key => $group) {
            $row = $this->rows[$key] ?? null;
            if (! $row || ! ($row['on'] ?? false)) {
                continue;
            }
            $rules["rows.{$key}.shape"] = ['required', 'in:text,note,parts'];
            if ($row['tbc'] ?? false) {
                continue;
            }

            if ($row['shape'] === 'parts') {
                $rules["rows.{$key}.parts"] = ['required', 'array', 'min:1', 'max:6'];
                foreach (array_keys($row['parts'] ?? []) as $i) {
                    $rules += $this->transRules("rows.{$key}.parts.{$i}.label", max: 120);
                    $rules += $this->transRules("rows.{$key}.parts.{$i}.value", max: 500);
                }
            } else {
                $rules += $this->transRules("rows.{$key}.text", max: 500);
                if ($row['shape'] === 'note') {
                    $rules += $this->transRules("rows.{$key}.note", max: 500);
                }
            }
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $names = [];
        foreach ($this->keys as $key => $group) {
            $label = Catalog::uiLabel('spec.'.$key, $key);
            $names += $this->transAttributes("rows.{$key}.text", $label);
            $names += $this->transAttributes("rows.{$key}.note", $label.': примечание');
            $names["rows.{$key}.parts"] = $label.': версии';
            foreach (array_keys($this->rows[$key]['parts'] ?? []) as $i) {
                $n = $i + 1;
                $names += $this->transAttributes("rows.{$key}.parts.{$i}.label", "{$label}: версия {$n}, название");
                $names += $this->transAttributes("rows.{$key}.parts.{$i}.value", "{$label}: версия {$n}, значение");
            }
        }

        return $names;
    }

    protected function messages(): array
    {
        return ['rows.*.parts.required' => 'Добавьте хотя бы одну версию для «:attribute».'] + $this->russianMessages();
    }

    public function save(): void
    {
        $this->validate();

        $model = ScaleModel::findOrFail($this->modelId);
        $changed = ['saved' => [], 'removed' => []];

        DB::transaction(function () use ($model, &$changed) {
            $existing = $model->specs()->get()->keyBy('key');
            $sort = 0;

            foreach ($this->keys as $key => $group) {
                $sort++;
                $row = $this->rows[$key] ?? null;
                $spec = $existing[$key] ?? null;

                if (! $row || ! ($row['on'] ?? false)) {
                    if ($spec) {
                        $spec->delete();
                        $changed['removed'][] = $key;
                    }

                    continue;
                }

                $spec ??= new ModelSpec(['scale_model_id' => $model->id, 'key' => $key]);
                $before = $spec->exists ? json_encode($spec->value) : null;
                $spec->fill(['group' => $group, 'value' => $this->valueOf($row), 'sort' => $sort]);

                if ($before !== json_encode($spec->value)) {
                    $changed['saved'][] = $key;
                }
                $spec->save();
            }
        });

        if ($changed['saved'] || $changed['removed']) {
            $model->touch();
            $this->logActivity('updated', $model, ['specs' => array_filter($changed)]);
        }

        session()->flash('status', 'Характеристики сохранены.');
    }

    /** Stored value for a row, in one of the three shapes. */
    private function valueOf(array $row): array
    {
        if ($row['tbc'] ?? false) {
            return $this->tbcValue();
        }

        return match ($row['shape']) {
            'parts' => ['parts' => array_map(
                fn (array $part) => [$this->transClean($part['label'] ?? null), $this->transClean($part['value'] ?? null)],
                array_values($row['parts']),
            )],
            'note' => ['v' => $this->transClean($row['text']), 'note' => $this->transClean($row['note'])],
            default => $this->transClean($row['text']),
        };
    }

    public function render(): View
    {
        $model = ScaleModel::findOrFail($this->modelId);

        $groups = [];
        foreach ($this->groups() as $group) {
            $keys = array_keys(array_filter($this->keys, fn ($g) => $g === $group));
            $groups[] = [
                'id' => $group,
                'label' => Catalog::uiLabel('grp.'.$group, $group),
                'keys' => array_map(fn ($key) => [
                    'key' => $key,
                    'label' => Catalog::uiLabel('spec.'.$key, $key),
                    'hasLabel' => Catalog::uiLabel('spec.'.$key, '') !== '',
                ], $keys),
            ];
        }

        return view('livewire.admin.items.models-specs', [
            'model' => $model,
            'groups' => $groups,
            'tbc' => $this->tbcValue(),
        ])->layout('components.layouts.admin', ['title' => 'Характеристики: '.$model->name]);
    }
}
