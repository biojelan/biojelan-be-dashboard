<?php

namespace App\Actions\Kilang;

use App\Models\Factory;
use App\Models\FinanceEntry;
use App\Models\Pickup;
use App\PickupStatus;

class FactoryInventory
{
    /** @return array{received_liter: float, sold_liter: float, stock_liter: float} */
    public function totals(Factory $factory): array
    {
        $received = (float) Pickup::query()->where('factory_id', $factory->factory_id)->where('pickups.status', PickupStatus::Completed)
            ->join('transactions_agent', 'transactions_agent.id', '=', 'pickups.transaction_agent_id')->sum('transactions_agent.volume_liter');
        $sold = (float) FinanceEntry::query()->where('factory_id', $factory->factory_id)->where('type', 'SALE')->sum('volume_liter');

        return ['received_liter' => round($received, 3), 'sold_liter' => round($sold, 3), 'stock_liter' => round($received - $sold, 3)];
    }
}
