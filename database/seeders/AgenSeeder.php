<?php

namespace Database\Seeders;

use App\Models\Agen;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $agenUser = User::query()
            ->where('email', 'agent@mail.com')
            ->where('role_id', User::AGEN_ROLE_ID)
            ->firstOrFail();

        Agen::query()->firstOrCreate(
            ['agen_id' => $agenUser->id],
            [
                'address' => 'Jl. Jenderal Sudirman No. 1, Bandar Lampung',
                'latitude' => '-5.429',
                'longitude' => '105.262',
                'bank_name' => 'MANDIRI',
                'account_number' => '1234567890',
                'open_at' => '08:00:00',
                'close_at' => '20:00:00',
                'open_days' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'],
                'is_open' => true,
                'stock_liter' => 50,
            ],
        );
    }
}
