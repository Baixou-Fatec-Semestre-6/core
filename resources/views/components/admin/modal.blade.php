{{--
    <x-admin.modal name="create-product" title="Cadastrar Produto" icon="plus">
        <form class="p-5 space-y-3.5 text-xs"> … </form>
    </x-admin.modal>

    Open:  <button x-on:click="$dispatch('open-modal', 'create-product')">
    Close: $dispatch('close-modal', 'create-product')  (ESC and backdrop click also close it)
--}}
@props([
    'name',
    'title',
    'icon' => null,
    'iconClass' => 'text-yellow-400',
    'maxWidth' => 'max-w-md',
])

<div
    x-data="{ open: false }"
    x-cloak
    x-show="open"
    x-on:open-modal.window="if (($event.detail?.name ?? $event.detail) === @js($name)) open = true"
    x-on:close-modal.window="if (($event.detail?.name ?? $event.detail) === @js($name)) open = false"
    x-on:keydown.escape.window="open = false"
    x-on:click.self="open = false"
    role="dialog"
    aria-modal="true"
    aria-label="{{ $title }}"
    class="fixed inset-0 bg-neutral-950/60 z-50 flex items-center justify-center"
>
    <div {{ $attributes->class(['bg-neutral-900 w-full rounded-xl shadow-2xl overflow-hidden border border-neutral-800', $maxWidth]) }}>
        <div class="p-4 bg-neutral-950 text-white border-b border-neutral-800 flex items-center justify-between">
            <h3 class="text-sm font-bold flex items-center space-x-2">
                @if ($icon)
                    <x-admin.icon :name="$icon" :size="'w-4 h-4 '.$iconClass" />
                @endif
                <span>{{ $title }}</span>
            </h3>
            <button type="button" x-on:click="open = false" class="text-neutral-400 hover:text-white" aria-label="Fechar">✕</button>
        </div>

        {{ $slot }}
    </div>
</div>
