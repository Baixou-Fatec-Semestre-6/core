@props(['padded' => false])

<div {{ $attributes->class(['bg-neutral-900 rounded-xl border border-neutral-800 shadow-xs', 'p-4' => $padded]) }}>
    {{ $slot }}
</div>
