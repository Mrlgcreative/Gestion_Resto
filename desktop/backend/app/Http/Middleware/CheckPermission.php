<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Non authentifié'], 401);
            }
            return redirect()->route('login');
        }

        if (!$request->user()->hasPermission($permission)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Accès non autorisé.'], 403);
            }
            
            abort(403, 'Accès non autorisé. Vous n\'avez pas la permission requise.');
        }

        return $next($request);
    }
}
