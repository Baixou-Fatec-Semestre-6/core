@extends('layouts.guest', ['title' => 'Recuperação de Senha Administrativa'])

@section('content')
    <div class="space-y-4">
        <div>
            <h2 class="text-base font-bold text-neutral-100">Recuperação de Acesso</h2>
            <p class="mt-1 text-xs text-neutral-400">
                Digite seu e-mail e enviaremos um link para redefinir sua senha.
            </p>
        </div>

        <!-- Tratamento de Mensagem de Sucesso -->
        @if (session('status'))
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-4">
            @csrf

            <!-- E-mail -->
            <div class="space-y-1">
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-neutral-300">
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
                    class="w-full rounded-lg border px-3.5 py-2.5 font-mono text-xs text-neutral-100 placeholder:text-neutral-500 focus:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-yellow-400/20 @error('email') border-red-500 bg-red-500/10 focus:border-red-400 @else border-neutral-700 bg-neutral-800/50 focus:border-yellow-400 @enderror"
                >
                @error('email')
                    <p class="font-mono text-[11px] font-semibold text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Enviar -->
            <button type="submit" class="w-full rounded-lg bg-yellow-400 py-2.5 text-xs font-bold uppercase tracking-wider text-neutral-950 shadow-md transition-colors hover:bg-yellow-300 active:bg-yellow-500">
                Enviar Link de Redefinição
            </button>
        </form>

        <div class="text-center pt-2">
            <a href="{{ route('admin.login') }}" class="text-xs font-semibold text-neutral-300 hover:text-yellow-400">
                &larr; Voltar para o login administrativo
            </a>
        </div>
    </div>
@endsection