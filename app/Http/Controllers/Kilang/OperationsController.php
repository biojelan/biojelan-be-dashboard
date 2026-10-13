<?php

namespace App\Http\Controllers\Kilang;

use App\Actions\Kilang\FactoryInventory;
use App\Actions\Kilang\SchedulePickup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kilang\SchedulePickupRequest;
use App\Models\Agen;
use App\Models\Pickup;
use App\Models\User;
use App\PickupStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OperationsController extends Controller
{
    public function index(Request $request, FactoryInventory $inventory): Response
    {
        /** @var User $user */ $user = $request->user();
        $factory = $user->admin->factory;
        $agentQuery = Agen::query()->select(['agen_id', 'address', 'latitude', 'longitude', 'stock_liter'])->with('user:id,name,email,phone')->whereHas('user', fn ($q) => $q->where('role_id', User::AGEN_ROLE_ID)->where('is_active', true));
        $agents = (clone $agentQuery)->orderByDesc('stock_liter')->paginate(20, ['*'], 'agents_page')->withQueryString();
        $eligible = (clone $agentQuery)->where('stock_liter', '>=', $factory->minimum_pickup_liter)->orderByDesc('stock_liter')->get();
        $pickups = Pickup::query()->where('factory_id', $factory->factory_id)->with(['driver:id,name,phone', 'driver.driver', 'agent:id,name,phone', 'agent.agen', 'transactionAgent:id,volume_liter'])
            ->latest('pickup_id')->paginate(20)->withQueryString();
        $drivers = User::query()->select(['id', 'name', 'phone'])->where('role_id', User::DRIVER_ROLE_ID)->where('is_active', true)->with('driver')->whereHas('driver')->get();
        $active = Pickup::query()->where('factory_id', $factory->factory_id)->whereNotIn('status', [PickupStatus::Completed, PickupStatus::Cancelled])->orderBy('date')->orderBy('time')->orderBy('stop_order')->get(['pickup_id', 'driver_id', 'status', 'destination', 'date', 'time', 'route_key', 'stop_order']);

        return Inertia::render('kilang/operations', ['agents' => $agents, 'eligibleAgents' => $eligible, 'pickups' => $pickups,
            'drivers' => $drivers, 'activePickups' => $active, 'inventory' => $inventory->totals($factory), 'totalAgentStock' => (float) $agentQuery->sum('stock_liter')]);
    }

    public function store(SchedulePickupRequest $request, SchedulePickup $schedule): RedirectResponse
    {
        /** @var User $user */ $user = $request->user();
        $schedule->handle($user->admin->factory, $request->pickupData());

        return back()->with('success', 'Rute pickup dibuat. Stok telah direservasi; Agent dapat menyetujui transaksi melalui API.');
    }

    public function threshold(Request $request): RedirectResponse
    {
        $data = $request->validate(['minimum_pickup_liter' => ['required', 'numeric', 'min:0.001', 'max:999999999', 'decimal:0,3']]);
        /** @var User $user */ $user = $request->user();
        $user->admin->factory->update($data);

        return back()->with('success', 'Batas minimum pengambilan diperbarui.');
    }
}
