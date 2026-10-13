<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKilangAccess
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_active && in_array($user->role_id, User::KILANG_ROLE_IDS, true), 403, 'Dashboard hanya untuk akun Kilang aktif.');
        abort_unless($user->admin()->exists(), 403, 'Akun Kilang belum terhubung ke Factory.');

        return $next($request);
    }
}
