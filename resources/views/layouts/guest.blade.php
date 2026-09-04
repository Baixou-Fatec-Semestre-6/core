<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Autenticação Administrativa' }} · {{ config('app.name', 'BAIXOU') }}</title>

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS (Vite / Fallback para CDN) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>

<body class="h-full flex items-center justify-center p-4 antialiased text-slate-800 bg-slate-950">

    <!-- Brilhos de Iluminação Ambiente -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-slate-800/30 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative z-10 space-y-6">
        <!-- Logotipo e Cabeçalho -->
        <div class="text-center space-y-2">
            <div
                class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-600 text-white font-bold text-xl shadow-lg shadow-blue-500/20 mb-1">
                BX
            </div>
            <h1 class="text-xl font-bold text-white tracking-wider flex items-center justify-center space-x-2">
                <span>BAIXOU</span>
                <span
                    class="text-xs px-2 py-0.5 rounded bg-slate-800 text-blue-400 font-mono font-medium border border-slate-700">BACKOFFICE</span>
            </h1>
            <p class="text-xs text-slate-400">Acesso restrito exclusivamente a administradores autorizados.</p>
        </div>

        <!-- Conteúdo do Card de Autenticação -->
        <div class="bg-white rounded-2xl p-8 shadow-2xl border border-slate-800/60 space-y-6">
            {{ $slot ?? '' }}
            @yield('content')
        </div>

        <div class="text-center text-[11px] text-slate-500 font-mono">
            &copy; {{ date('Y') }} {{ config('app.name', 'BAIXOU') }} · Backoffice
        </div>
    </div>

    @stack('scripts')
</body>

</html>