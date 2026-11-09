<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-950">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Autenticação Administrativa' }} · {{ config('app.name', 'BAIXOU') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap"
        rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/admin.css')
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    @livewireStyles
</head>

<body class="flex min-h-full items-center justify-center bg-neutral-950 p-4 font-sans antialiased text-neutral-200">
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-yellow-400/10 blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-neutral-800/30 blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md space-y-6">
        <div class="text-center space-y-2">
            <div class="mb-1 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-yellow-400 text-xl font-bold text-neutral-950 shadow-lg shadow-yellow-400/20">
                BX
            </div>
            <h1 class="flex items-center justify-center gap-2 text-xl font-bold tracking-wider text-white">
                <span>BAIXOU</span>
                <span class="rounded border border-neutral-700 bg-neutral-800 px-2 py-0.5 font-mono text-xs font-medium text-yellow-400">BACKOFFICE</span>
            </h1>
            <p class="text-xs text-neutral-400">Acesso restrito exclusivamente a administradores autorizados.</p>
        </div>

        <div class="space-y-6 rounded-2xl border border-neutral-800/60 bg-neutral-900 p-8 shadow-2xl">
            {{ $slot ?? '' }}
            @yield('content')
        </div>

        <div class="text-center font-mono text-[11px] text-neutral-500">
            &copy; {{ date('Y') }} {{ config('app.name', 'BAIXOU') }} · Backoffice
        </div>
    </div>

    @stack('scripts')
    @livewireScripts
</body>

</html>