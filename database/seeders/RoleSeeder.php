<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            1 => 'superadmin',
            2 => 'admin',
            3 => 'manajer_finance',
            4 => 'manajer_operasional',
            5 => 'driver',
            6 => 'agen',
            7 => 'klien',
        ];

        foreach ($roles as $id => $role) {
            DB::table('role')->updateOrInsert(['id' => $id], ['role' => $role]);
        }
    }
}
