<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Gate::define('kilang-users', fn (User $user): bool => in_array($user->role_id, [User::SUPERADMIN_ROLE_ID, User::ADMIN_ROLE_ID], true));
        Gate::define('kilang-operations', fn (User $user): bool => in_array($user->role_id, [User::SUPERADMIN_ROLE_ID, User::ADMIN_ROLE_ID, User::MANAGER_OPERATIONAL_ROLE_ID], true));
        Gate::define('kilang-finance', fn (User $user): bool => in_array($user->role_id, [User::SUPERADMIN_ROLE_ID, User::ADMIN_ROLE_ID, User::MANAGER_FINANCE_ROLE_ID], true));

        RateLimiter::for('api-auth', function (Request $request): array {
            $email = $request->input('email');
            $accountKey = is_string($email) ? strtolower($email) : '';
            $response = fn (Request $request, array $headers) => response()->json([
                'data' => (object) [],
                'message' => 'Too many requests. Please try again later.',
            ], 429, $headers);

            return [
                Limit::perMinute(30)->by('ip:'.$request->ip())->response($response),
                Limit::perMinute(5)->by('account:'.$accountKey.'|'.$request->ip())->response($response),
            ];
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
