@extends('layouts.guest', ['title' => 'Entrar no Painel Admin'])

@section('content')
   @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-xs font-medium">
            {{ session('status') }}
        </div>
    @endif

    <!-- Formulário de Login do Administrador -->
    <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
        @csrf

        <!-- E-mail -->
        <div class="space-y-1">
            <label for="email" class="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                E-mail do Administrador
            </label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                required 
                autofocus
                value="{{ old('email') }}" 
                placeholder="admin@baixou.internal"
                class="w-full text-xs px-3.5 py-2.5 rounded-lg border @error('email') border-red-500 bg-red-50/20 @else border-slate-300 bg-slate-50 @enderror focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 font-mono text-slate-900"
            >
            @error('email')
                <p class="text-[11px] text-red-600 font-semibold font-mono">{{ $message }}</p>
            @enderror
        </div>

        <!-- Senha -->
        <div class="space-y-1">
            <div class="flex items-center justify-between">
                <label for="password" class="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                    Senha
                </label>
                @if (Route::has('admin.password.request'))
                    <a href="{{ route('admin.password.request') }}" class="text-xs text-blue-600 hover:underline font-medium">
                        Redefinir senha
                    </a>
                @endif
            </div>
            <input 
                type="password" 
                id="password" 
                name="password" 
                required 
                autocomplete="new-password"
                placeholder="••••••••••••"
                class="w-full text-xs px-3.5 py-2.5 rounded-lg border @error('password') border-red-500 bg-red-50/20 @else border-slate-300 bg-slate-50 @enderror focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 font-mono text-slate-900"
            >
            @error('password')
                <p class="text-[11px] text-red-600 font-semibold font-mono">{{ $message }}</p>
            @enderror
        </div>

        <!-- Lembrar de Mim -->
        <div class="flex items-center justify-between text-xs pt-1">
            <label class="flex items-center space-x-2 cursor-pointer select-none text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span>Lembrar nesta estação</span>
            </label>
            <span class="text-[11px] text-slate-400 font-mono">Sessão: 8 horas</span>
        </div>

        <!-- Botão de Envio -->
        <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition-colors shadow-md shadow-blue-500/20">
            Entrar no Painel Administrativo
        </button>
    </form>
@endsection
