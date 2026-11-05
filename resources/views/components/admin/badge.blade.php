@props(['variant' => 'success'])

@php
    $classes = match ($variant) {
        // status pills
        'success' => 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-300',
        'pending' => 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-300',
        'muted' => 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-neutral-700 text-neutral-300',
        'restricted' => 'px-2 py-0.5 rounded-sm text-[10px] font-bold bg-orange-500/10 text-orange-400 border border-orange-500/20',
        // sidebar counters
        'count' => 'px-1.5 py-0.5 rounded-sm text-[10px] font-mono bg-neutral-800 text-neutral-300',
        'count-danger' => 'px-1.5 py-0.5 rounded-full text-[10px] font-bold font-mono bg-red-500/20 text-red-300 border border-red-500/30',
        'count-purple' => 'px-1.5 py-0.5 rounded-sm text-[10px] font-mono bg-purple-500/20 text-purple-300 border border-purple-500/30',
        default => throw new \InvalidArgumentException("Unknown badge variant [{$variant}]."),
    };
@endphp

<span {{ $attributes->class($classes) }}>{{ $slot }}</span>
