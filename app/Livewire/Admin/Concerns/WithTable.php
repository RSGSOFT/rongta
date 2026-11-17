<?php

namespace App\Livewire\Admin\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Search, sort and pagination for admin list screens.
 *
 *   use WithTable;
 *   protected array $searchable = ['name', 'slug'];      // plain or JSON {ru,uz,en} columns
 *   protected array $sortable = ['name', 'sort', 'created_at'];
 *   protected string $defaultSort = 'sort';               // optional, default "id"
 *   protected string $defaultDir = 'asc';                 // optional, default "asc"
 *
 *   $rows = $this->tableRows(Partner::query()->with('region'));
 *
 * View: <x-admin.search />, <x-admin.th sort="name" :by="$sortBy" :dir="$sortDir">,
 *       {{ $rows->links('components.admin.pagination') }}
 * Extra filters: add your own public properties and call $this->resetPage() in their updated hooks
 * (any property named "filter..." resets the page automatically).
 */
trait WithTable
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: '')]
    public string $sortBy = '';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDir = 'asc';

    public int $perPage = 25;

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable ?? [], true)) {
            return;
        }

        if ($this->currentSort() === $column) {
            $this->sortDir = $this->currentDir() === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDir = 'asc';
        }
        $this->sortBy = $column;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Filter value for a query: null when the select is on "all". */
    protected function filterValue(mixed $value): mixed
    {
        return $value === '' || $value === null ? null : $value;
    }

    /** Livewire trait hook: any "filter..." property change goes back to page 1. */
    public function updatedWithTable($property): void
    {
        if (str_starts_with((string) $property, 'filter')) {
            $this->resetPage();
        }
    }

    protected function currentSort(): string
    {
        $sortable = $this->sortable ?? [];

        return in_array($this->sortBy, $sortable, true) ? $this->sortBy : ($this->defaultSort ?? 'id');
    }

    protected function currentDir(): string
    {
        if ($this->sortBy === '' || ! in_array($this->sortBy, $this->sortable ?? [], true)) {
            return ($this->defaultDir ?? 'asc') === 'desc' ? 'desc' : 'asc';
        }

        return $this->sortDir === 'desc' ? 'desc' : 'asc';
    }

    /** Case-insensitive "contains" search over the $searchable columns. */
    protected function applySearch(Builder $query, ?array $columns = null): Builder
    {
        $term = trim($this->search);
        $columns ??= $this->searchable ?? [];
        if ($term === '' || ! $columns) {
            return $query;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($term)).'%';

        return $query->where(function (Builder $q) use ($columns, $like) {
            foreach ($columns as $column) {
                $wrapped = $q->getQuery()->getGrammar()->wrap($q->qualifyColumn($column));
                // CAST makes JSON columns searchable as text on MySQL and SQLite alike.
                $q->orWhereRaw("LOWER(CAST({$wrapped} AS CHAR)) LIKE ?", [$like]);
            }
        });
    }

    protected function applySort(Builder $query): Builder
    {
        $query->orderBy($query->qualifyColumn($this->currentSort()), $this->currentDir());

        // Stable order for equal values.
        $key = $query->getModel()->getQualifiedKeyName();
        if ($query->qualifyColumn($this->currentSort()) !== $key) {
            $query->orderBy($key);
        }

        return $query;
    }

    /** Search + sort + paginate in one call. */
    protected function tableRows(Builder $query): LengthAwarePaginator
    {
        return $this->applySort($this->applySearch($query))->paginate($this->perPage);
    }
}
