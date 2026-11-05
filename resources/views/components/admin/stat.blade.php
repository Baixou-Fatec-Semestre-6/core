@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'default',   // default | accent | success | danger
])

@php
    $card = $tone === 'danger' ? 'border-red-500/30 bg-red-500/10' : 'border-neutral-800 bg-neutral-900';
    $labelColor = $tone === 'danger' ? 'text-red-400' : 'text-neutral-400';
    $valueColor = match ($tone) {
        'accent' => 'text-yellow-400',
        'success' => 'text-emerald-400',
        'danger' => 'text-red-400',
        default => 'text-neutral-100',
    };
    $hintColor = $tone === 'danger' ? 'text-red-400' : 'text-neutral-400';
@endphp

<div {{ $attributes->class(['p-4 rounded-xl border shadow-xs', $card]) }}>
    <span class="text-[11px] font-bold uppercase tracking-wider block {{ $labelColor }}">{{ $label }}</span>
    <div class="mt-1 text-2xl font-black font-mono {{ $valueColor }}">{{ $value }}</div>
    @if ($hint)
        <p class="text-[10px] mt-1 {{ $hintColor }}">{{ $hint }}</p>
    @endif
</div>
