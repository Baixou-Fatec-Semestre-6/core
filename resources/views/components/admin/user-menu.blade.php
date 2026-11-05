@php
    $user = auth()->user();
    $name = $user?->name ?? 'Administrador';
    $initials = str($name)
        ->explode(' ')
        ->filter()
        ->map(fn (string $part) => str($part)->substr(0, 1)->upper())
        ->take(2)
        ->implode('');
@endphp

<div class="p-3 border-t border-neutral-800 bg-neutral-950 flex items-center justify-between">
    <div class="flex items-center space-x-2.5 overflow-hidden">
        <div class="w-8 h-8 rounded-full bg-yellow-400 flex items-center justify-center font-bold text-xs text-neutral-950 shrink-0">
            {{ $initials }}
        </div>
        <div class="overflow-hidden">
            <p class="text-xs font-semibold text-white truncate">{{ $name }}</p>
            <p class="text-[10px] text-neutral-400 truncate">{{ $user?->email }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" title="Encerrar Sessão (Logout)" aria-label="Encerrar Sessão"
            class="text-neutral-400 hover:text-red-400 p-1.5 rounded-sm hover:bg-neutral-800 transition-colors">
            <x-admin.icon name="logout" />
        </button>
    </form>
</div>
