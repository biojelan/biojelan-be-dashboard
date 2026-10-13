<?php

namespace App\Http\Controllers\Kilang;

use App\Actions\Kilang\FactoryInventory;
use App\Actions\Kilang\FinancialReport;
use App\Http\Controllers\Controller;
use App\Models\Agen;
use App\Models\AgentApplication;
use App\Models\Pickup;
use App\Models\User;
use App\PickupStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, FactoryInventory $inventory, FinancialReport $report): Response
    {
        /** @var User $user */
        $user = $request->user();
        $factory = $user->admin->factory;
        $stats = ['users' => null, 'pending_requests' => null, 'agent_stock' => null, 'active_pickups' => null, 'inventory' => null, 'finance' => null];
        if ($user->can('kilang-users')) {
            $stats['users'] = User::query()->count();
            $stats['pending_requests'] = AgentApplication::query()->where('status', 'PENDING')->count();
        }
        if ($user->can('kilang-operations') || $user->can('kilang-finance')) {
            $stats['inventory'] = $inventory->totals($factory);
            $stats['agent_stock'] = (float) Agen::query()->whereHas('user', fn ($q) => $q->where('role_id', User::AGEN_ROLE_ID)->where('is_active', true))->sum('stock_liter');
            $stats['active_pickups'] = Pickup::query()->where('factory_id', $factory->factory_id)->whereNotIn('status', [PickupStatus::Completed, PickupStatus::Cancelled])->count();
        }
        if ($user->can('kilang-finance')) {
            $stats['finance'] = $report->totals($factory, '2020-01-01', today()->toDateString());
        }

        return Inertia::render('kilang/dashboard', ['stats' => $stats]);
    }
}
