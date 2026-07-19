<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /* Handle incoming request */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required permissions to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
