<?php

use App\Models\Price;
use App\PriceType;
use Database\Seeders\PriceSeeder;

test('price seeder creates the initial client and agent prices', function () {
    $this->seed(PriceSeeder::class);

    $clientPrice = Price::query()->where('price_type', PriceType::Client)->firstOrFail();
    $agentPrice = Price::query()->where('price_type', PriceType::Agent)->firstOrFail();

    expect($clientPrice->start_date->toDateString())->toBe('2020-01-01');
    expect($clientPrice->price_per_liter)->toBe('5000.00');
    expect($agentPrice->start_date->toDateString())->toBe('2020-01-01');
    expect($agentPrice->price_per_liter)->toBe('7000.00');

    $this->seed(PriceSeeder::class);

    expect(Price::query()->count())->toBe(2);
});
