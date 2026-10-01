<?php

use App\Models\Price;
use App\PriceType;
use Database\Seeders\PriceSeeder;

test('the public price endpoint returns the active client price', function () {
    $this->seed(PriceSeeder::class);
    Price::factory()->agent()->create(['price_per_liter' => '9000.00']);

    $this->getJson('/api/price')
        ->assertOk()
        ->assertJsonPath('data.price_id', 1)
        ->assertJsonPath('data.price_per_liter', 5000)
        ->assertJsonPath('data.price_type', PriceType::Client->value)
        ->assertJsonPath('data.start_date', '2020-01-01')
        ->assertJsonPath('data.end_date', null)
        ->assertJsonPath('message', 'Success get daily price!');
});

test('the public price endpoint returns 404 when no client price is active', function () {
    Price::factory()->create([
        'start_date' => today()->addDay(),
        'end_date' => null,
    ]);

    $this->getJson('/api/price')
        ->assertNotFound()
        ->assertJsonPath('message', 'Failed get daily price! Price is unavailable.');
});
