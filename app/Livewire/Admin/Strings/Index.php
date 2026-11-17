<?php

namespace App\Livewire\Admin\Strings;

use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\UiString;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /admin/strings: every interface string of the public site, searchable by key or text. */
class Index extends Component
{
    use RequiresStaff, WithTable;

    protected array $searchable = ['key', 'ru', 'uz', 'en'];

    protected array $sortable = ['key', 'updated_at'];

    protected string $defaultSort = 'key';

    /** '' | any | ru | uz | en: strings with an empty translation. */
    #[Url(as: 'missing', except: '')]
    public string $filterMissing = '';

    /** Key prefix (the part before the first dot), e.g. "hero". */
    #[Url(as: 'group', except: '')]
    public string $filterGroup = '';

    public static function missing(Builder $query, array $locales): Builder
    {
        return $query->where(function (Builder $q) use ($locales) {
            foreach ($locales as $locale) {
                $q->orWhereNull($locale)->orWhere($locale, '');
            }
        });
    }

    public function render(): View
    {
        $group = trim($this->filterGroup);

        $query = UiString::query()
            ->when($this->filterMissing === 'any', fn (Builder $q) => self::missing($q, ['ru', 'uz', 'en']))
            ->when(in_array($this->filterMissing, ['ru', 'uz', 'en'], true), fn (Builder $q) => self::missing($q, [$this->filterMissing]))
            ->when($group !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('key', $group)
                ->orWhere('key', 'like', str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $group).'.%')));

        $groups = UiString::query()->orderBy('key')->pluck('key')
            ->map(fn (string $key) => explode('.', $key, 2)[0])
            ->countBy()
            ->map(fn (int $count, string $prefix) => $prefix.' ('.$count.')');

        return view('livewire.admin.strings.index', [
            'rows' => $this->tableRows($query),
            'groups' => $groups,
            'missingCount' => self::missing(UiString::query(), ['ru', 'uz', 'en'])->count(),
        ])->layout('components.layouts.admin', ['title' => 'Тексты интерфейса']);
    }
}
