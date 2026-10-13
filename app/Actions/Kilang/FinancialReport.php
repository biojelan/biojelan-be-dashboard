<?php

namespace App\Actions\Kilang;

use App\Models\Factory;
use App\Models\FinanceEntry;
use App\Models\Pickup;
use App\PickupStatus;

class FinancialReport
{
    /** @return array<string, float|int|string> */
    public function totals(Factory $factory, string $from, string $to): array
    {
        $entries = FinanceEntry::query()->where('factory_id', $factory->factory_id)->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
        $income = (float) (clone $entries)->where('type', 'SALE')->sum('amount');
        $expenses = (float) (clone $entries)->where('type', 'EXPENSE')->sum('amount');
        $purchases = Pickup::query()->where('factory_id', $factory->factory_id)->where('pickups.status', PickupStatus::Completed)
            ->whereDate('completed_at', '>=', $from)->whereDate('completed_at', '<=', $to)
            ->join('transactions_agent', 'transactions_agent.id', '=', 'pickups.transaction_agent_id');
        $purchaseCost = (float) (clone $purchases)->sum('transactions_agent.total_price');

        return [
            'from' => $from, 'to' => $to, 'income' => round($income, 2), 'purchase_cost' => round($purchaseCost, 2),
            'operating_expense' => round($expenses, 2), 'total_expense' => round($purchaseCost + $expenses, 2),
            'profit' => round($income - $purchaseCost - $expenses, 2),
            'received_liter' => (float) (clone $purchases)->sum('transactions_agent.volume_liter'),
            'sold_liter' => (float) (clone $entries)->where('type', 'SALE')->sum('volume_liter'),
        ];
    }
}
