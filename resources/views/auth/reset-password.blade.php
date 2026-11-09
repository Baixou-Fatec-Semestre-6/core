@extends('layouts.guest', ['title' => 'Redefinir Senha Administrativa'])

@section('content')
    <div class="space-y-4">
        {{-- Cabeçalho da tela de redefinição --}}
        <div>
            <h2 class="text-base font-bold text-neutral-100">Definir Nova Senha</h2>
            <p class="mt-1 text-xs text-neutral-400">
                Crie uma nova senha de acesso segura para sua conta administrativa.
            </p>
        </div>

        {{-- Alerta de Sucesso (Exibido quando a senha for redefinida com êxito) --}}
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-medium text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        {{-- Formulário de Redefinição de Senha --}}
        <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-4">
            @csrf

            {{-- Token oculto capturado do parâmetro da rota --}}
            <input type="hidden" name="token" value="{{ $token ?? request()->route('token') }}">

            {{-- Campo de E-mail (Somente Leitura - Protegido contra alteração) --}}
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-neutral-300">
                        E-mail do Administrador
                    </label>
                    <span class="flex items-center space-x-1 font-mono text-[10px] text-neutral-400">
                        <svg class="h-3 w-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Somente leitura</span>
                    </span>
                </div>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    readonly
                    value="{{ old('email', request()->email ?? '') }}" 
                    placeholder="admin@baixou.internal"
                    class="w-full cursor-not-allowed select-none rounded-lg border border-neutral-700 bg-neutral-800/30 px-3.5 py-2.5 font-mono text-xs text-neutral-500 transition-colors focus:border-neutral-700 focus:outline-none focus:ring-0"
                >
                {{-- Exibição de erro específico caso haja divergência no e-mail vinculado ao token --}}
                @error('email')
                    <p class="mt-1 flex items-center space-x-1 font-mono text-[11px] font-semibold text-red-300">
                        <span>✕</span>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Campo da Nova Senha (com foco automático) --}}
            <div class="space-y-1">
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-neutral-300">
                    Nova Senha
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autofocus
                    placeholder="Mínimo de 8 caracteres"
                    class="w-full rounded-lg border px-3.5 py-2.5 font-mono text-xs text-neutral-100 transition-colors placeholder:text-neutral-500 focus:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-yellow-400/20 @error('password') border-red-500 bg-red-500/10 focus:border-red-400 @else border-neutral-700 bg-neutral-800/50 focus:border-yellow-400 @enderror"
                >
                {{-- Mensagem de erro de validação da senha --}}
                @error('password')
                    <p class="mt-1 flex items-center space-x-1 font-mono text-[11px] font-semibold text-red-300">
                        <span>✕</span>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Campo de Confirmação da Nova Senha --}}
            <div class="space-y-1">
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-neutral-300">
                    Confirmar Nova Senha
                </label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    required 
                    placeholder="Repita a nova senha"
                    class="w-full rounded-lg border border-neutral-700 bg-neutral-800/50 px-3.5 py-2.5 font-mono text-xs text-neutral-100 placeholder:text-neutral-500 transition-colors focus:border-yellow-400 focus:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-yellow-400/20"
                >
            </div>

            {{-- Botão de Submissão --}}
            <button type="submit" class="flex w-full items-center justify-center space-x-2 rounded-lg bg-yellow-400 py-2.5 text-xs font-bold uppercase tracking-wider text-neutral-950 shadow-md shadow-yellow-400/20 transition-colors hover:bg-yellow-300 active:bg-yellow-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>Redefinir e Salvar Senha</span>
            </button>
        </form>

        {{-- Link de Retorno para a Tela de Login Administrativo --}}
        <div class="text-center pt-2">
            <a href="{{ route('admin.login') }}" class="text-xs font-semibold text-neutral-300 transition-colors hover:text-yellow-400">
                ← Voltar para o login administrativo
            </a>
        </div>
    </div>
@endsection