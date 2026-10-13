<?php

namespace Database\Seeders;

use App\Models\AgentApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgentApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $user = User::query()->where('email', 'client.demo@mail.com')->first();
        if ($user === null) {
            $user = new User;
            $user->forceFill(['name' => 'Client Demo', 'email' => 'client.demo@mail.com', 'password' => 'client123', 'role_id' => User::CLIENT_ROLE_ID, 'is_active' => true, 'is_verified' => true, 'email_verified_at' => now()])->save();
        }
        AgentApplication::query()->firstOrCreate(['user_id' => $user->id], ['status' => 'PENDING', 'profile' => [
            'address' => 'Jl. Contoh No. 2, Bandar Lampung', 'latitude' => -5.3971, 'longitude' => 105.2668, 'bank_name' => 'MANDIRI', 'account_number' => '1234567890', 'open_at' => '08:00', 'close_at' => '17:00', 'open_days' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat'],
        ]]);
    }
}
