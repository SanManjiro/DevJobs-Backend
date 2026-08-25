<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActive
{
    /**
     * Bloque l'accès si le compte est désactivé (is_active = false).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()->is_active) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        return $next($request);
    }
}
