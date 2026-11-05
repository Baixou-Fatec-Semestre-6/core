@props([
    'variant' => 'primary',   // primary | secondary | link-accent | link-danger | link-success
    'href' => null,           // when set, renders an <a>
    'icon' => null,           // Icon name, shown before the label
    'type' => 'button',
])

@php
    $classes = match ($variant) {
        'primary' => 'inline-flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs font-bold bg-yellow-400 hover:bg-yellow-300 text-neutral-950 shadow-xs transition-colors',
        'secondary' => 'inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold bg-neutral-900 border border-neutral-700 hover:bg-neutral-800/50 text-neutral-300 transition-colors',
        'link-accent' => 'text-yellow-400 hover:underline font-semibold',
        'link-danger' => 'text-red-400 hover:underline font-semibold',
        'link-success' => 'text-emerald-400 hover:underline font-semibold',
        default => throw new \InvalidArgumentException("Unknown button variant [{$variant}]."),
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-admin.icon :name="$icon" size="w-3.5 h-3.5" />
        @endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-admin.icon :name="$icon" size="w-3.5 h-3.5" />
        @endif
        <span>{{ $slot }}</span>
    </button>
@endif
