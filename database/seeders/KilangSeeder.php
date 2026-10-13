<?php

namespace Database\Seeders;

use App\Models\Factory;
use App\Models\Pickup;
use App\Models\User;
use App\PickupStatus;
use Illuminate\Database\Seeder;

class KilangSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([UserSeeder::class, FactorySeeder::class]);
        $factory = Factory::query()->oldest('factory_id')->firstOrFail();
        foreach (User::query()->whereIn('role_id', User::KILANG_ROLE_IDS)->get() as $user) {
            $user->admin()->firstOrCreate([], ['factory_id' => $factory->factory_id]);
        }
        Pickup::query()->whereNull('factory_id')->update(['factory_id' => $factory->factory_id]);
        Pickup::query()->where('status', PickupStatus::Completed)->whereNull('completed_at')->update(['completed_at' => now()]);
    }
}
