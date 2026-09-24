<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $roles = [
            1 => 'superadmin',
            2 => 'admin',
            3 => 'manajer_finance',
            4 => 'manajer_operasional',
            5 => 'driver',
            6 => 'agent',
        ];

        foreach ($roles as $roleId => $role) {
            $email = $role.'@mail.com';
            $user = User::query()->where('email', $email)->first();

            if ($roleId === User::AGEN_ROLE_ID && $user === null) {
                $user = User::query()->where('email', 'agen@mail.com')->first();

                if ($user !== null) {
                    $user->email = $email;

                    if (Hash::check('agen123', $user->password)) {
                        $user->password = 'agent123';
                    }

                    $user->save();
                }
            }

            if ($user !== null) {
                continue;
            }

            $user = new User;
            $user->forceFill([
                'name' => str_replace('_', ' ', ucfirst($role)),
                'email' => $email,
                'password' => $role.'123',
                'role_id' => $roleId,
                'email_verified_at' => now(),
                'is_verified' => true,
                'is_active' => true,
            ])->save();
        }
    }
}
