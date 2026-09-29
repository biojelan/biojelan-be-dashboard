<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $driver = User::query()
            ->where('email', 'driver@mail.com')
            ->where('role_id', User::DRIVER_ROLE_ID)
            ->firstOrFail();

        Driver::query()->firstOrCreate(
            ['driver_id' => $driver->id],
            [
                'plate_number' => 'BE 1234 BJ',
                'type_vehicle' => 'pickup',
            ],
        );
    }
}
