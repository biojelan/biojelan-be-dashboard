<?php

namespace App\Actions\Kilang;

use App\ClientTransactionStatus;
use App\Models\Agen;
use App\Models\Factory;
use App\Models\Pickup;
use App\Models\Price;
use App\Models\TransactionAgent;
use App\Models\User;
use App\PickupStatus;
use App\PriceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SchedulePickup
{
    /** @param array{driver_id: int, date: string, time: string, stops: list<array{agent_id: int, volume_liter: float}>} $data */
    public function handle(Factory $factory, array $data): void
    {
        DB::transaction(function () use ($factory, $data): void {
            $factory = Factory::query()->lockForUpdate()->findOrFail($factory->factory_id);
            $driver = User::query()->whereKey($data['driver_id'])->where('role_id', User::DRIVER_ROLE_ID)->where('is_active', true)->whereHas('driver')->first();
            if ($driver === null) {
                throw ValidationException::withMessages(['driver_id' => 'Driver aktif dengan profil kendaraan wajib dipilih.']);
            }
            $price = Price::query()->where('price_type', PriceType::Agent)->whereDate('start_date', '<=', today())
                ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
                ->latest('start_date')->latest('price_id')->first();
            if ($price === null) {
                throw ValidationException::withMessages(['stops' => 'Harga Agent belum tersedia. Atur melalui Keuangan.']);
            }
            $routeKey = (string) Str::uuid();
            $ids = array_column($data['stops'], 'agent_id');
            $agents = Agen::query()->whereKey($ids)->orderBy('agen_id')->lockForUpdate()->get()->keyBy('agen_id');
            foreach ($data['stops'] as $order => $stop) {
                $agent = $agents->get($stop['agent_id']);
                $volume = round((float) $stop['volume_liter'], 3);
                $totalPrice = round($volume * (float) $price->price_per_liter, 2);
                if ($totalPrice > 9999999999999.99) {
                    throw ValidationException::withMessages(["stops.$order.volume_liter" => 'Total harga melebihi kapasitas nilai transaksi.']);
                }
                if ($agent === null || ! $agent->user()->where('role_id', User::AGEN_ROLE_ID)->where('is_active', true)->exists()
                    || $agent->stock_liter < (float) $factory->minimum_pickup_liter || $volume < (float) $factory->minimum_pickup_liter || $volume > $agent->stock_liter) {
                    throw ValidationException::withMessages(["stops.$order.volume_liter" => 'Volume harus memenuhi batas minimum dan tidak melebihi stok Agent aktif.']);
                }
                $agent->update(['stock_liter' => round($agent->stock_liter - $volume, 3)]);
                $transaction = TransactionAgent::query()->create([
                    'agent_id' => $agent->agen_id, 'driver_id' => $driver->id, 'price_id' => $price->price_id,
                    'volume_liter' => $volume, 'total_price' => $totalPrice,
                    'status' => ClientTransactionStatus::Pending, 'transaction_note' => 'Penugasan Kilang '.$routeKey,
                ]);
                Pickup::query()->create([
                    'transaction_agent_id' => $transaction->id, 'factory_id' => $factory->factory_id,
                    'agent_id' => $agent->agen_id, 'driver_id' => $driver->id, 'date' => $data['date'], 'time' => $data['time'],
                    'status' => PickupStatus::Assigned, 'destination' => $agent->address, 'route_key' => $routeKey, 'stop_order' => $order + 1,
                ]);
            }
        });
    }
}
