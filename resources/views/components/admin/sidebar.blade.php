<aside class="w-64 bg-neutral-900 text-neutral-300 flex flex-col shrink-0 z-20 border-r border-neutral-800 shadow-lg select-none">
    {{-- Brand --}}
    <div class="h-16 flex items-center justify-between px-5 border-b border-neutral-800 bg-neutral-950">
        <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-sm bg-yellow-400 flex items-center justify-center font-bold text-neutral-950 text-base">
                BX
            </div>
            <div>
                <span class="font-bold text-base text-white tracking-wider block leading-tight">BAIXOU</span>
                <span class="text-[10px] tracking-wider uppercase font-semibold text-neutral-400 block">Painel Admin</span>
            </div>
        </div>
        <x-admin.badge variant="restricted">RESTRITO</x-admin.badge>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" aria-label="Navegação administrativa">
        @foreach ($items as $item)
            <x-admin.nav-item
                :href="$item['href']"
                :active="$item['active']"
                :icon="$item['icon']"
                :icon-class="$item['iconClass']"
                :badge="$item['badge']"
                :badge-variant="$item['badgeVariant']"
            >
                {{ $loop->iteration }}. {{ $item['label'] }}
            </x-admin.nav-item>
        @endforeach
    </nav>

    <x-admin.user-menu />
</aside>
