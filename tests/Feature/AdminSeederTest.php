<?php

use App\Models\Admin;
use App\Models\Factory;
use App\Models\User;
use Database\Seeders\AdminSeeder;

test('admin seeder creates an admin profile associated with the factory', function (): void {
    $this->seed(AdminSeeder::class);

    $user = User::query()->where('email', 'admin@mail.com')->firstOrFail();
    $admin = Admin::query()->where('admin_id', $user->id)->firstOrFail();

    expect($user->role_id)->toBe(User::ADMIN_ROLE_ID)
        ->and($admin->factory)->toBeInstanceOf(Factory::class)
        ->and($admin->factory->factory_name)->toBe('Kilang Biojelan');
});
