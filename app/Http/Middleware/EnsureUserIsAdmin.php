<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Manipula uma requisição recebida.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário está autenticado e é um administrador
        if (! $request->user() || ! $request->user()->isAdmin()) {
            abort(403, 'Acesso não autorizado.');
        }

        return $next($request);
    }
}
