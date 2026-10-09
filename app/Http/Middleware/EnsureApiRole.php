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
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $roleIds = array_filter(array_map(
            fn (string $role): ?int => match ($role) {
                'driver' => User::DRIVER_ROLE_ID,
                'agent' => User::AGEN_ROLE_ID,
                'client' => User::CLIENT_ROLE_ID,
                'admin' => User::ADMIN_ROLE_ID,
                'superadmin' => User::SUPERADMIN_ROLE_ID,
                default => null,
            },
            $roles,
        ));

        if (! $user instanceof User || ! in_array($user->role_id, $roleIds, true)) {
            return response()->json([
                'data' => (object) [],
                'message' => 'Failed access resource! User unauthorized.',
            ], 403);
        }

        return $next($request);
    }
}
