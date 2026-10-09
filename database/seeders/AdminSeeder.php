<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([UserSeeder::class, FactorySeeder::class]);

        $admin = User::query()
            ->where('email', 'admin@mail.com')
            ->where('role_id', User::ADMIN_ROLE_ID)
            ->firstOrFail();
        $factory = Factory::query()->oldest('factory_id')->firstOrFail();

        Admin::query()->updateOrCreate(
            ['admin_id' => $admin->id],
            ['factory_id' => $factory->factory_id],
        );
    }
}
