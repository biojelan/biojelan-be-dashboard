<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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
            6 => 'agen',
        ];

        foreach ($roles as $roleId => $role) {
            if (User::query()->where('email', $role.'@mail.com')->exists()) {
                continue;
            }

            $user = new User;
            $user->forceFill([
                'name' => str_replace('_', ' ', ucfirst($role)),
                'email' => $role.'@mail.com',
                'password' => $role.'123',
                'role_id' => $roleId,
                'email_verified_at' => now(),
                'is_verified' => true,
                'is_active' => true,
            ])->save();
        }
    }
}
