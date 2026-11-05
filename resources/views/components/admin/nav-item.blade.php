@props([
    'href' => null,
    'active' => false,
    'icon',
    'iconClass' => '',
    'badge' => null,
    'badgeVariant' => 'count',
])

<a
    href="{{ $href ?? '#' }}"
    @if ($href) wire:navigate @endif
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition-colors',
        'bg-yellow-400 text-neutral-950' => $active,
        'text-neutral-300 hover:bg-neutral-800 hover:text-white' => ! $active,
    ]) }}
>
    <div class="flex items-center space-x-3">
        <x-admin.icon :name="$icon" :size="$active ? 'w-4 h-4' : trim('w-4 h-4 '.$iconClass)" />
        <span>{{ $slot }}</span>
    </div>

    @if (filled($badge))
        <x-admin.badge :variant="$badgeVariant">{{ $badge }}</x-admin.badge>
    @endif
</a>
