<div class="space-y-6">
    @if (session('status'))
        <div role="status" class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-medium text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    @error('credentials')
        <div role="alert" class="flex items-start gap-2 rounded-lg border border-red-500/30 bg-red-500/10 p-3 text-xs text-red-300">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <form wire:submit="login" class="space-y-4">
        <div class="space-y-1">
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-neutral-300">
                E-mail do Administrador
            </label>
            <input
                type="email"
                id="email"
                wire:model="email"
                required
                autofocus
                autocomplete="username"
                placeholder="admin@dominio.com"
                class="w-full rounded-lg border px-3.5 py-2.5 font-mono text-xs text-neutral-100 placeholder:text-neutral-500 focus:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-yellow-400/20 @error('email') border-red-500 bg-red-500/10 focus:border-red-400 @else border-neutral-700 bg-neutral-800/50 focus:border-yellow-400 @enderror"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
            >
            @error('email')
                <p id="email-error" class="text-[11px] font-semibold text-red-300">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-neutral-300">
                    Senha
                </label>
                @if (Route::has('admin.password.request'))
                    <a href="{{ route('admin.password.request') }}" class="text-xs font-medium text-yellow-400 hover:underline">
                        Redefinir senha
                    </a>
                @endif
            </div>
            <div x-data="{ visible: false }" class="relative">
                <input
                    id="password"
                    x-bind:type="visible ? 'text' : 'password'"
                    wire:model="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••••••"
                    class="w-full rounded-lg border px-3.5 py-2.5 pr-10 font-mono text-xs text-neutral-100 placeholder:text-neutral-500 focus:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-yellow-400/20 @error('password') border-red-500 bg-red-500/10 focus:border-red-400 @else border-neutral-700 bg-neutral-800/50 focus:border-yellow-400 @enderror"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                >
                <button
                    type="button"
                    x-on:click="visible = !visible"
                    x-bind:aria-label="visible ? 'Ocultar senha' : 'Mostrar senha'"
                    class="absolute right-3 top-2.5 text-neutral-400 hover:text-neutral-200"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p id="password-error" class="text-[11px] font-semibold text-red-300">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between pt-1 text-xs">
            <label for="remember" class="flex cursor-pointer select-none items-center gap-2 text-neutral-300">
                <input
                    id="remember"
                    type="checkbox"
                    wire:model="remember"
                    class="rounded border-neutral-700 bg-neutral-800 text-yellow-400 focus:ring-yellow-400"
                >
                <span>Lembrar nesta estação</span>
            </label>
            <span class="font-mono text-[11px] text-neutral-400">Sessão: 8h</span>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="login"
            class="w-full rounded-lg bg-yellow-400 py-3 text-xs font-bold uppercase tracking-wider text-neutral-950 shadow-md shadow-yellow-400/20 transition-colors hover:bg-yellow-300 active:bg-yellow-500 disabled:cursor-wait disabled:opacity-70"
        >
            <span wire:loading.remove wire:target="login">Entrar no Painel Administrativo</span>
            <span wire:loading wire:target="login">Entrando…</span>
        </button>
    </form>
</div>
