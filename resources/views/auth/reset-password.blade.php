@extends('layouts.guest', ['title' => 'Redefinir Senha Administrativa'])

@section('content')
    <div class="space-y-4">
        {{-- Cabeçalho da tela de redefinição --}}
        <div>
            <h2 class="text-base font-bold text-slate-900">Definir Nova Senha</h2>
            <p class="text-xs text-slate-500 mt-1">
                Crie uma nova senha de acesso segura para sua conta administrativa.
            </p>
        </div>

        {{-- Alerta de Sucesso (Exibido quando a senha for redefinida com êxito) --}}
        @if (session('status'))
            <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-xs font-medium">
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
                    <label for="email" class="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                        E-mail do Administrador
                    </label>
                    <span class="text-[10px] text-slate-400 font-mono flex items-center space-x-1">
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-100 text-slate-500 font-mono cursor-not-allowed select-none focus:outline-none focus:ring-0 focus:border-slate-300 transition-colors"
                >
                {{-- Exibição de erro específico caso haja divergência no e-mail vinculado ao token --}}
                @error('email')
                    <p class="text-[11px] text-red-600 font-semibold font-mono mt-1 flex items-center space-x-1">
                        <span>✕</span>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Campo da Nova Senha (com foco automático) --}}
            <div class="space-y-1">
                <label for="password" class="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                    Nova Senha
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autofocus
                    placeholder="Mínimo de 8 caracteres"
                    class="w-full text-xs px-3.5 py-2.5 rounded-lg border @error('password') border-red-500 bg-red-50/20 text-red-900 @else border-slate-300 bg-slate-50 text-slate-900 @enderror focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 font-mono transition-colors"
                >
                {{-- Mensagem de erro de validação da senha --}}
                @error('password')
                    <p class="text-[11px] text-red-600 font-semibold font-mono mt-1 flex items-center space-x-1">
                        <span>✕</span>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Campo de Confirmação da Nova Senha --}}
            <div class="space-y-1">
                <label for="password_confirmation" class="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                    Confirmar Nova Senha
                </label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    required 
                    placeholder="Repita a nova senha"
                    class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-900 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 font-mono transition-colors"
                >
            </div>

            {{-- Botão de Submissão --}}
            <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition-colors shadow-md shadow-blue-500/20 flex items-center justify-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>Redefinir e Salvar Senha</span>
            </button>
        </form>

        {{-- Link de Retorno para a Tela de Login Administrativo --}}
        <div class="text-center pt-2">
            <a href="{{ route('admin.login') }}" class="text-xs text-slate-600 hover:text-slate-900 font-semibold transition-colors">
                ← Voltar para o login administrativo
            </a>
        </div>
    </div>
@endsection