<?php

use App\Models\Agen;
use App\Models\User;
use Database\Seeders\AgenSeeder;
use Illuminate\Support\Facades\Hash;

test('agen seeding creates the agen account and complete agen profile', function () {
    $this->seed(AgenSeeder::class);

    $user = User::query()->where('email', 'agent@mail.com')->firstOrFail();
    $agen = Agen::query()->findOrFail($user->id);

    expect($user->role_id)->toBe(6);
    expect(Hash::check('agent123', $user->password))->toBeTrue();
    expect($agen->address)->toBe('Jl. Jenderal Sudirman No. 1, Bandar Lampung');
    expect($agen->latitude)->toBe('-5.429');
    expect($agen->longitude)->toBe('105.262');
    expect($agen->bank_name)->toBe('MANDIRI');
    expect($agen->account_number)->toBe('1234567890');
    expect($agen->open_at)->toBe('08:00:00');
    expect($agen->close_at)->toBe('20:00:00');
    expect($agen->open_days)->toBe(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']);
    expect($agen->is_open)->toBeTrue();
    expect($agen->stock_liter)->toBe(50.0);
});

test('repeated agen seeding preserves an existing agen profile', function () {
    $this->seed(AgenSeeder::class);
    $user = User::query()->where('email', 'agent@mail.com')->firstOrFail();
    $user->agen->update(['address' => 'Custom Address']);

    $this->seed(AgenSeeder::class);

    $this->assertDatabaseCount('agen', 1);
    $this->assertDatabaseHas('agen', ['agen_id' => $user->id, 'address' => 'Custom Address']);
});
