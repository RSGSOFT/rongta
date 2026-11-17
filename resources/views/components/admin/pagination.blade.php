{{-- Livewire paginator view: {{ $rows->links('components.admin.pagination') }} --}}
@php $pageName = $paginator->getPageName(); @endphp
<nav class="flex flex-wrap items-center justify-between gap-2 text-xs text-steel" aria-label="Страницы">
    <span>
        @if ($paginator->total() > 0)
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} из {{ $paginator->total() }}
        @else
            0 записей
        @endif
    </span>
    @if ($paginator->hasPages())
        <span class="flex items-center gap-1.5">
            <button type="button" wire:click="previousPage('{{ $pageName }}')" @disabled($paginator->onFirstPage())
                    class="inline-flex min-h-8 items-center rounded-m border border-line bg-label px-2.5 font-semibold text-ink hover:bg-paper disabled:cursor-not-allowed disabled:opacity-40">Назад</button>
            <span class="px-1">стр. {{ $paginator->currentPage() }} из {{ $paginator->lastPage() }}</span>
            <button type="button" wire:click="nextPage('{{ $pageName }}')" @disabled(! $paginator->hasMorePages())
                    class="inline-flex min-h-8 items-center rounded-m border border-line bg-label px-2.5 font-semibold text-ink hover:bg-paper disabled:cursor-not-allowed disabled:opacity-40">Вперёд</button>
        </span>
    @endif
</nav>
