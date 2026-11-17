{{--
  Header cell. With sort="column" it becomes a button calling sort('column') on the component
  (trait WithTable); pass :by="$sortBy" :dir="$sortDir" to show the current direction.
--}}
@props(['sort' => null, 'by' => null, 'dir' => 'asc', 'align' => 'left'])
@php $active = $sort && $by === $sort; @endphp
<th scope="col" @if ($sort) aria-sort="{{ $active ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}" @endif {{ $attributes->class(['!text-right' => $align === 'right']) }}>
    @if ($sort)
        <button type="button" wire:click="sort('{{ $sort }}')" class="inline-flex items-center gap-1 font-semibold hover:text-ink {{ $active ? 'text-ink' : '' }}">
            {{ $slot }}
            <span aria-hidden="true" class="text-[10px] {{ $active ? '' : 'opacity-30' }}">{{ $active && $dir === 'desc' ? '▼' : '▲' }}</span>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
