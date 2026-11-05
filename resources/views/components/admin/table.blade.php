{{--
    <x-admin.table>
        <x-slot:head> <x-admin.th>…</x-admin.th> </x-slot:head>
        <tr>…</tr>
        <x-slot:footer> pagination </x-slot:footer>   (optional)
    </x-admin.table>
--}}
<x-admin.card {{ $attributes->class('overflow-hidden') }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="bg-neutral-800/50 border-b border-neutral-800 text-neutral-400 font-bold uppercase text-[10px] tracking-wider">
                    {{ $head }}
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-800 text-neutral-300">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-neutral-800 px-4 py-3">
            {{ $footer }}
        </div>
    @endisset
</x-admin.card>
