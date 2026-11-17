<div id="tray">
    @if ($items->isNotEmpty())
        <div class="tray" role="region" aria-label="{{ ui('cmp.tray') }}">
            <div class="wrap tray-in">
                <div class="tray-list">
                    @foreach ($items as $slug => $name)
                        <span class="tray-item" wire:key="tray-{{ $slug }}">{{ $name }}<button type="button" id="tray-x-{{ $slug }}" wire:click="remove(@js($slug))" aria-label="{{ ui('cmp.remove') }}: {{ $name }}">×</button></span>
                    @endforeach
                </div>
                <button type="button" class="btn-clear" id="tray-clear" wire:click="clear">{{ ui('cmp.clear') }}</button>
                <a class="btn btn-p btn-sm" id="tray-go" href="{{ lroute('compare') }}">{{ ui('cmp.go', ['n' => $items->count()]) }}</a>
            </div>
        </div>
    @endif
</div>
