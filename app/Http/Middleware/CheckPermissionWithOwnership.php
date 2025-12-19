<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionWithOwnership
{
    /**
     * Handle an incoming request.
     * 
     * Vérifie si l'utilisateur a la permission "view_all" ou "view_own".
     * Si c'est "view_own", le contrôleur devra filtrer les données par user_id.
     *
     * Usage: middleware('permission.ownership:sessions') 
     * Vérifie sessions.view_all OU sessions.view_own
     * 
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $resource  Le nom de la ressource (sessions, orders, reports, payments)
     */
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $viewAllPermission = "{$resource}.view_all";
        $viewOwnPermission = "{$resource}.view_own";

        // L'utilisateur peut voir tout OU ses propres ressources
        if ($user->hasPermission($viewAllPermission) || $user->hasPermission($viewOwnPermission)) {
            // Stocker dans la requête si l'utilisateur ne peut voir que ses propres ressources
            $request->merge([
                'can_view_all' => $user->hasPermission($viewAllPermission),
                'view_own_only' => !$user->hasPermission($viewAllPermission) && $user->hasPermission($viewOwnPermission),
            ]);

            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        abort(403, 'Accès non autorisé. Vous n\'avez pas la permission requise.');
    }
}
