{{--
    Download row: format badge, title, version / date / size, and a download link when the
    document has a file. Placeholder documents never show their sample version, date or size.
    Use inside <ul class="files">.   <x-site.file-row :document="$document" />
--}}
@props(['document'])
@php
    $modelName = $document->scaleModel?->name;
    $title = $document->tr('title');
    $full = trim($title.($title !== '' && $modelName ? ': ' : '').($modelName ?? ''));

    $path = (string) ($document->file_path ?? '');
    $href = null;
    if ($path !== '') {
        $href = preg_match('#^(https?:)?//#', $path) || str_starts_with($path, '/')
            ? $path
            : (is_file(public_path($path)) ? asset($path) : asset('storage/'.ltrim($path, '/')));
    }

    $known = ! $document->is_placeholder
        && (filled($document->version) || $document->released_on || $document->tr('size_label') !== '');
    $meta = $known ? ui('file.meta', [
        'ver' => filled($document->version) ? $document->version : '—',
        'date' => $document->released_on?->format('d.m.Y') ?? '—',
        'size' => $document->tr('size_label') ?: '—',
    ]) : null;
@endphp
<li {{ $attributes->class('file') }} @if ($document->slug) id="{{ $document->slug }}" @endif>
    <span class="file-type">{{ $document->format ?: '—' }}</span>
    <div>
        <b>{{ $full }}</b>
        <span class="file-meta">@if ($meta){{ $meta }}@else<span class="tbc">{{ ui('val.tbc') }}</span>@endif</span>
    </div>
    @if ($href)
        <a class="btn btn-s btn-sm" href="{{ $href }}" download aria-label="{{ ui('file.get') }}: {{ $full }}">{{ ui('file.get') }}</a>
    @else
        <span class="small muted">{{ ui('site.file.pending') }}</span>
    @endif
</li>
