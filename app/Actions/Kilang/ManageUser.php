<?php

namespace App\Actions\Kilang;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageUser
{
    /** @param array<string, mixed> $data */
    public function save(User $actor, array $data, ?User $target = null): User
    {
        return DB::transaction(function () use ($actor, $data, $target): User {
            $factory = $actor->admin?->factory;
            abort_if($factory === null, 403);
            Factory::query()->whereKey($factory->factory_id)->lockForUpdate()->firstOrFail();
            $user = $target === null ? new User : User::query()->lockForUpdate()->findOrFail($target->id);
            $roleId = (int) $data['role_id'];
            if ($user->exists && $roleId !== $user->role_id && ! ($user->role_id === User::CLIENT_ROLE_ID && $roleId === User::AGEN_ROLE_ID) && ! ($user->role_id === null && $roleId === User::CLIENT_ROLE_ID)) {
                throw ValidationException::withMessages(['role_id' => 'Perubahan role hanya diperbolehkan dari Client menjadi Agent.']);
            }
            if ($user->exists && $actor->id === $user->id && ! $data['is_active']) {
                throw ValidationException::withMessages(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
            }
            $user->fill(Arr::only($data, ['name', 'email', 'phone']));
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->forceFill(['role_id' => $roleId, 'is_active' => $data['is_active']]);
            if (! $user->exists) {
                $user->forceFill(['email_verified_at' => now(), 'is_verified' => true]);
            }
            $user->save();
            $profile = $data['profile'] ?? [];
            if ($roleId === User::AGEN_ROLE_ID) {
                $agent = $user->agen()->firstOrNew();
                $agent->fill(Arr::only($profile, ['address', 'latitude', 'longitude', 'bank_name', 'account_number', 'open_at', 'close_at', 'open_days']));
                if (! $agent->exists) {
                    $agent->fill(['stock_liter' => 0, 'is_open' => false]);
                }
                $agent->save();
            }
            if ($roleId === User::DRIVER_ROLE_ID) {
                $driver = $user->driver()->firstOrNew();
                $driver->fill(Arr::only($profile, ['plate_number', 'type_vehicle']))->save();
            }
            if (in_array($roleId, User::KILANG_ROLE_IDS, true)) {
                $user->admin()->firstOrCreate([], ['factory_id' => $factory->factory_id]);
            }
            if (! $user->is_active || ! empty($data['password'])) {
                $user->tokens()->delete();
            }

            return $user;
        });
    }
}
