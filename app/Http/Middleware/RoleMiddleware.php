<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            if (!$user->role) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no role assigned',
                ], Response::HTTP_FORBIDDEN);
            }

            $userRole = $user->role->slug;

            if (!in_array($userRole, $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                ], Response::HTTP_FORBIDDEN);
            }

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}
