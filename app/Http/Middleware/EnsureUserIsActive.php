<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->activo) {
            $request->user()?->tokens()->delete();

            return response()->json([
                'mensaje' => 'Tu cuenta está desactivada.',
            ], 403);
        }

        return $next($request);
    }
}
