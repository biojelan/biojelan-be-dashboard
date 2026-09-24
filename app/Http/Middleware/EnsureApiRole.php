<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $roleId = match ($role) {
            'agent' => User::AGEN_ROLE_ID,
            'client' => User::CLIENT_ROLE_ID,
            default => null,
        };

        if (! $user instanceof User || $roleId === null || $user->role_id !== $roleId) {
            return response()->json([
                'data' => (object) [],
                'message' => 'Failed access resource! User unauthorized.',
            ], 403);
        }

        return $next($request);
    }
}
