{{-- Trilingual textarea bound to {model}.ru / .uz / .en. Same props as x-admin.trans-input, plus rows. --}}
@props(['label' => null, 'model', 'hint' => null, 'required' => false, 'live' => false, 'rows' => 3])
<x-admin.trans-input :label="$label" :model="$model" :hint="$hint" :required="$required" :live="$live" :rows="$rows" multiline {{ $attributes }} />
