{{-- Session "status" (success) and "error" messages. Already rendered by x-admin.page. --}}
@if (session('status'))
    <div role="status" {{ $attributes->class('mb-4 flex items-start gap-2 rounded-m border border-ok/30 bg-ok-soft px-3 py-2.5 text-ok') }}>
        <svg viewBox="0 0 24 24" class="mt-0.5 h-4 w-4 flex-none" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
        <span class="font-medium">{{ session('status') }}</span>
    </div>
@endif
@if (session('error'))
    <div role="alert" {{ $attributes->class('mb-4 flex items-start gap-2 rounded-m border border-danger/30 bg-danger-soft px-3 py-2.5 text-danger') }}>
        <svg viewBox="0 0 24 24" class="mt-0.5 h-4 w-4 flex-none" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 7v6M12 17h.01"/></svg>
        <span class="font-medium">{{ session('error') }}</span>
    </div>
@endif
