{{--
Admin shell: sidebar + top bar + scrollable content area.

Blade page: <x-layouts.admin title="Algo"> … </x-layouts.admin>
Livewire page: #[Layout('components.layouts.admin')] #[Title('Algo')]

Header buttons are page-specific; push them from the page view:
@push('header-actions') <x-admin.button …>Novo</x-admin.button> @endpush
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-950">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ filled($title ?? null) ? $title.' · ' : '' }}BAIXOU · Painel Administrativo (Restrito)</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:500,600,700"
        rel="stylesheet">

    @vite('resources/css/admin.css')
    @livewireStyles
</head>

<body class="h-full flex overflow-hidden font-sans text-neutral-200 antialiased">
    <x-admin.sidebar />

    <main class="flex-1 flex flex-col min-w-0 bg-neutral-800 overflow-hidden">
        <header class="h-16 bg-neutral-900 border-b border-neutral-800 flex items-center justify-between px-6 z-10">
            <div class="flex items-center space-x-3">
                <h1 class="text-base font-bold text-neutral-100">{{ $title ?? 'Painel Admin' }}</h1>
            </div>

            <div class="flex items-center space-x-3">
                @stack('header-actions')
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>

</html>