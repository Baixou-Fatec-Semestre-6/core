@extends('layouts.guest', ['title' => 'Recuperação de Senha Administrativa'])

@section('content')
    <div class="space-y-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Recuperação de Acesso</h2>
            <p class="text-xs text-slate-500 mt-1">
                Digite seu e-mail e enviaremos um link para redefinir sua senha.
            </p>
        </div>

        <!-- Tratamento de Mensagem de Sucesso -->
        @if (session('status'))
            <div class="p-3 text-xs font-semibold text-green-700 bg-green-50 border border-green-200 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-4">
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

            <!-- Enviar -->
            <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition-colors shadow-md">
                Enviar Link de Redefinição
            </button>
        </form>

        <div class="text-center pt-2">
            <a href="{{ route('admin.login') }}" class="text-xs text-slate-600 hover:text-slate-900 font-semibold">
                &larr; Voltar para o login administrativo
            </a>
        </div>
    </div>
@endsection