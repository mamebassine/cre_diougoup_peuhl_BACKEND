<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{

public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::guard('api')->user();

        // ❌ pas connecté
        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié'
            ], 401);
        }

        // ❌ rôle non autorisé
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Accès refusé'
            ], 403);
        }

        return $next($request);
    }
}