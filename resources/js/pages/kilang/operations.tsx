import { Head, useForm, usePage, usePoll } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Building2,
    Check,
    Droplets,
    ExternalLink,
    MapPin,
    Plus,
    Route,
    SlidersHorizontal,
    Truck,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    store,
    threshold,
} from '@/actions/App/Http/Controllers/Kilang/OperationsController';
import {
    EmptyState,
    ErrorList,
    fieldClass,
    PageHeading,
    PaginationNav,
    Panel,
    StatCard,
    StatusPill,
    tableClass,
} from '@/components/kilang/common';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type {
    AgentProfile,
    Inventory,
    KilangShared,
    Pagination,
    Person,
    PickupRow,
} from '@/types/kilang';
import { dateLabel, liter, localToday } from '@/types/kilang';
type Props = {
    agents: Pagination<AgentProfile>;
    eligibleAgents: AgentProfile[];
    pickups: Pagination<PickupRow>;
    drivers: Person[];
    activePickups: PickupRow[];
    inventory: Inventory;
    totalAgentStock: number;
};
function ScheduleForm({
    agents,
    drivers,
    onDone,
}: {
    agents: AgentProfile[];
    drivers: Person[];
    onDone: () => void;
}) {
    const { factory } = usePage<KilangShared>().props.kilang;
    const [selected, setSelected] = useState('');
    const form = useForm({
        driver_id: '',
        date: localToday(),
        time: '08:00',
        stops: [] as { agent_id: number; volume_liter: number }[],
    });
    function add() {
        const agent = agents.find((item) => item.agen_id === Number(selected));
        if (!agent) {
            return;
        }
        form.setData('stops', [
            ...form.data.stops,
            { agent_id: agent.agen_id, volume_liter: agent.stock_liter },
        ]);
        setSelected('');
    }
    function move(position: number, direction: number) {
        const stops = [...form.data.stops];
        const other = position + direction;
        if (other < 0 || other >= stops.length) {
            return;
        }
        [stops[position], stops[other]] = [stops[other], stops[position]];
        form.setData('stops', stops);
    }
    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(store.url(), { onSuccess: onDone });
    }
    const waypointCoordinates = form.data.stops
        .map((stop) => {
            const agent = agents.find((item) => item.agen_id === stop.agent_id);
            return agent ? `${agent.latitude},${agent.longitude}` : '';
        })
        .join('|');
    const mapUrl = `https://www.google.com/maps/dir/?api=1&origin=${factory?.latitude},${factory?.longitude}&destination=${factory?.latitude},${factory?.longitude}&waypoints=${encodeURIComponent(waypointCoordinates)}`;
    return (
        <form className="space-y-5" onSubmit={submit}>
            <ErrorList errors={form.errors} />
            <div className="grid gap-4 sm:grid-cols-3">
                <div className="grid gap-2 sm:col-span-3">
                    <Label htmlFor="pickup-driver">Driver</Label>
                    <select
                        id="pickup-driver"
                        required
                        className={fieldClass}
                        value={form.data.driver_id}
                        onChange={(event) =>
                            form.setData('driver_id', event.target.value)
                        }
                    >
                        <option value="">Pilih Driver aktif</option>
                        {drivers.map((driver) => (
                            <option key={driver.id} value={driver.id}>
                                {driver.name} · {driver.driver?.plate_number}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="pickup-date">Tanggal pengambilan</Label>
                    <input
                        id="pickup-date"
                        type="date"
                        min={localToday()}
                        required
                        className={fieldClass}
                        value={form.data.date}
                        onChange={(event) =>
                            form.setData('date', event.target.value)
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="pickup-time">Jam berangkat</Label>
                    <input
                        id="pickup-time"
                        type="time"
                        required
                        className={fieldClass}
                        value={form.data.time}
                        onChange={(event) =>
                            form.setData('time', event.target.value)
                        }
                    />
                </div>
            </div>
            <div>
                <Label htmlFor="pickup-agent">Tujuan Agent</Label>
                <p className="mt-1 mb-3 text-xs text-muted-foreground">
                    Hanya Agent dengan stok minimal{' '}
                    {liter(factory?.minimum_pickup_liter ?? 50)}. Urutkan titik
                    sesuai rute yang diinginkan.
                </p>
                <div className="flex gap-2">
                    <select
                        id="pickup-agent"
                        className={fieldClass}
                        value={selected}
                        onChange={(event) => setSelected(event.target.value)}
                    >
                        <option value="">Pilih Agent yang siap diambil</option>
                        {agents
                            .filter(
                                (agent) =>
                                    !form.data.stops.some(
                                        (stop) =>
                                            stop.agent_id === agent.agen_id,
                                    ),
                            )
                            .map((agent) => (
                                <option
                                    key={agent.agen_id}
                                    value={agent.agen_id}
                                >
                                    {agent.user?.name} ·{' '}
                                    {liter(agent.stock_liter)}
                                </option>
                            ))}
                    </select>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={!selected || form.data.stops.length >= 20}
                        onClick={add}
                    >
                        <Plus size={15} />
                        Tambah
                    </Button>
                </div>
            </div>
            <ol className="space-y-3">
                {form.data.stops.map((stop, position) => {
                    const agent = agents.find(
                        (item) => item.agen_id === stop.agent_id,
                    );
                    return (
                        <li
                            key={stop.agent_id}
                            className="rounded-xl border bg-muted/25 p-4"
                        >
                            <div className="flex items-start gap-3">
                                <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                                    {position + 1}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-semibold">
                                        {agent?.user?.name}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {agent?.address}
                                    </p>
                                    <div className="mt-3 flex flex-wrap items-center gap-2">
                                        <label
                                            className="text-xs"
                                            htmlFor={`volume-${position}`}
                                        >
                                            Ambil (L)
                                        </label>
                                        <input
                                            id={`volume-${position}`}
                                            required
                                            type="number"
                                            step="0.001"
                                            min={factory?.minimum_pickup_liter}
                                            max={agent?.stock_liter}
                                            className={`${fieldClass} w-28`}
                                            value={stop.volume_liter}
                                            onChange={(event) =>
                                                form.setData(
                                                    'stops',
                                                    form.data.stops.map(
                                                        (item, i) =>
                                                            i === position
                                                                ? {
                                                                      ...item,
                                                                      volume_liter:
                                                                          Number(
                                                                              event
                                                                                  .target
                                                                                  .value,
                                                                          ),
                                                                  }
                                                                : item,
                                                    ),
                                                )
                                            }
                                        />
                                        <span className="text-xs text-muted-foreground">
                                            Stok{' '}
                                            {liter(agent?.stock_liter ?? 0)}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        type="button"
                                        disabled={position === 0}
                                        onClick={() => move(position, -1)}
                                        aria-label="Naikkan urutan"
                                    >
                                        <ArrowUp size={14} />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        type="button"
                                        disabled={
                                            position ===
                                            form.data.stops.length - 1
                                        }
                                        onClick={() => move(position, 1)}
                                        aria-label="Turunkan urutan"
                                    >
                                        <ArrowDown size={14} />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        type="button"
                                        onClick={() =>
                                            form.setData(
                                                'stops',
                                                form.data.stops.filter(
                                                    (item) =>
                                                        item.agent_id !==
                                                        stop.agent_id,
                                                ),
                                            )
                                        }
                                        aria-label="Hapus tujuan"
                                    >
                                        <X size={14} />
                                    </Button>
                                </div>
                            </div>
                        </li>
                    );
                })}
            </ol>
            {!!form.data.stops.length && (
                <a
                    href={mapUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 text-xs font-semibold text-primary"
                >
                    <ExternalLink size={14} />
                    Lihat rute di Google Maps
                </a>
            )}
            <div className="rounded-xl bg-amber-100/50 p-3 text-xs leading-relaxed text-amber-900 dark:bg-amber-900/20 dark:text-amber-200">
                Stok langsung direservasi setelah penugasan disimpan. Agent
                perlu menerima transaksi melalui API sebelum Driver
                menyelesaikan pickup.
            </div>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onDone}>
                    Batal
                </Button>
                <Button
                    disabled={form.processing || !form.data.stops.length}
                    type="submit"
                >
                    {form.processing ? 'Menyimpan…' : 'Buat penugasan'}
                </Button>
            </div>
        </form>
    );
}
export default function Operations({
    agents,
    eligibleAgents,
    pickups,
    drivers,
    activePickups,
    inventory,
    totalAgentStock,
}: Props) {
    const { factory } = usePage<KilangShared>().props.kilang;
    const [scheduleOpen, setScheduleOpen] = useState(false);
    const settings = useForm({
        minimum_pickup_liter: factory?.minimum_pickup_liter ?? '50',
    });
    usePoll(15000, {
        only: [
            'activePickups',
            'pickups',
            'inventory',
            'totalAgentStock',
            'eligibleAgents',
            'agents',
        ],
    });
    return (
        <>
            <Head title="Operasional Kilang" />
            <PageHeading
                eyebrow="Collection & logistics"
                title="Manajemen Operasional"
                description="Susun rute pengambilan, pantau aktivitas Driver, dan lihat persediaan minyak dalam jaringan."
                action={
                    <Button
                        onClick={() => setScheduleOpen(true)}
                        disabled={!eligibleAgents.length || !drivers.length}
                    >
                        <Plus size={16} />
                        Buat penugasan
                    </Button>
                }
            />
            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Stok Factory"
                    value={liter(inventory.stock_liter)}
                    detail="Total diterima dikurangi penjualan"
                    icon={Building2}
                />
                <StatCard
                    label="Stok seluruh Agent"
                    value={liter(totalAgentStock)}
                    detail="Tersedia pada akun Agent aktif"
                    icon={Droplets}
                />
                <StatCard
                    label="Agent siap diambil"
                    value={eligibleAgents.length}
                    detail={`Batas minimum ${liter(factory?.minimum_pickup_liter ?? 50)}`}
                    icon={Check}
                />
                <StatCard
                    label="Pickup aktif"
                    value={activePickups.length}
                    detail="Status diperbarui setiap 15 detik"
                    icon={Truck}
                />
            </div>
            <div className="mb-6 grid gap-5 xl:grid-cols-[1fr_320px]">
                <Panel
                    title="Aktivitas Driver"
                    description="Semua Driver aktif dan penugasan yang sedang berlangsung."
                >
                    {drivers.length ? (
                        <div className="space-y-3">
                            {drivers.map((driver) => {
                                const tasks = activePickups.filter(
                                    (pickup) => pickup.driver_id === driver.id,
                                );
                                const latest = [...tasks].sort(
                                    (first, second) =>
                                        second.pickup_id - first.pickup_id,
                                )[0];
                                const moving = latest?.route_key
                                    ? tasks
                                          .filter(
                                              (task) =>
                                                  task.route_key ===
                                                  latest.route_key,
                                          )
                                          .sort(
                                              (first, second) =>
                                                  first.stop_order -
                                                  second.stop_order,
                                          )[0]
                                    : latest;
                                return (
                                    <div
                                        key={driver.id}
                                        className="rounded-xl border p-4"
                                    >
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <div className="flex items-center gap-3">
                                                <span className="rounded-xl bg-primary/8 p-2.5 text-primary">
                                                    <Truck size={18} />
                                                </span>
                                                <div>
                                                    <p className="text-sm font-semibold">
                                                        {driver.name}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {
                                                            driver.driver
                                                                ?.plate_number
                                                        }{' '}
                                                        ·{' '}
                                                        {
                                                            driver.driver
                                                                ?.type_vehicle
                                                        }
                                                    </p>
                                                </div>
                                            </div>
                                            {moving ? (
                                                <StatusPill
                                                    status={moving.status}
                                                />
                                            ) : (
                                                <span className="rounded-full bg-muted px-3 py-1 text-xs text-muted-foreground">
                                                    Belum ada penugasan
                                                </span>
                                            )}
                                        </div>
                                        {tasks.length > 0 && (
                                            <div className="mt-4 space-y-2 border-t pt-3">
                                                {tasks.map((task) => (
                                                    <div
                                                        key={task.pickup_id}
                                                        className="flex flex-wrap items-center justify-between gap-2 text-xs"
                                                    >
                                                        <span className="flex items-center gap-2 text-muted-foreground">
                                                            <MapPin size={12} />
                                                            {
                                                                task.stop_order
                                                            }.{' '}
                                                            {task.destination}
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {dateLabel(
                                                                task.date,
                                                            )}{' '}
                                                            ·{' '}
                                                            {task.time.slice(
                                                                0,
                                                                5,
                                                            )}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <EmptyState
                            title="Belum ada Driver aktif"
                            description="Tambahkan akun Driver dan profil kendaraannya di Manajemen User."
                        />
                    )}
                </Panel>
                <Panel
                    title="Batas pengambilan"
                    description="Perubahan berlaku untuk penugasan berikutnya."
                >
                    <SlidersHorizontal
                        className="mb-5 text-primary"
                        size={24}
                    />
                    <form
                        className="space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            settings.patch(threshold.url());
                        }}
                    >
                        <ErrorList errors={settings.errors} />
                        <div className="grid gap-2">
                            <Label htmlFor="pickup-minimum">
                                Minimum volume (liter)
                            </Label>
                            <input
                                id="pickup-minimum"
                                type="number"
                                min="0.001"
                                step="0.001"
                                required
                                className={fieldClass}
                                value={settings.data.minimum_pickup_liter}
                                onChange={(event) =>
                                    settings.setData(
                                        'minimum_pickup_liter',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={settings.processing}
                        >
                            Simpan batas minimum
                        </Button>
                    </form>
                    <div className="mt-6 border-t pt-4">
                        <p className="text-xs font-semibold">
                            Minyak sudah diterima
                        </p>
                        <p className="mt-2 text-2xl font-bold">
                            {liter(inventory.received_liter)}
                        </p>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Pickup COMPLETED yang terhubung dengan Factory ini.
                        </p>
                    </div>
                </Panel>
            </div>
            <Panel
                title="Persediaan Agent"
                description="Jumlah stok tersedia setelah reservasi transaksi Driver–Agent."
                className="mb-6"
            >
                <div className="overflow-x-auto rounded-xl border">
                    <table className={tableClass}>
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Alamat pengambilan</th>
                                <th>Stok tersedia</th>
                                <th>Kesiapan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {agents.data.map((agent) => (
                                <tr key={agent.agen_id}>
                                    <td>
                                        <p className="font-semibold whitespace-nowrap">
                                            {agent.user?.name}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {agent.user?.phone ?? '—'}
                                        </p>
                                    </td>
                                    <td className="max-w-xs text-xs text-muted-foreground">
                                        {agent.address}
                                    </td>
                                    <td className="font-semibold whitespace-nowrap">
                                        {liter(agent.stock_liter)}
                                    </td>
                                    <td>
                                        <span
                                            className={`rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap ${agent.stock_liter >= Number(factory?.minimum_pickup_liter ?? 50) ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}
                                        >
                                            {agent.stock_liter >=
                                            Number(
                                                factory?.minimum_pickup_liter ??
                                                    50,
                                            )
                                                ? 'Siap diambil'
                                                : 'Belum minimum'}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {!agents.data.length && (
                        <EmptyState title="Belum ada Agent aktif" />
                    )}
                </div>
                <PaginationNav page={agents} />
            </Panel>
            <Panel
                title="Riwayat penugasan"
                description="Setiap titik pengambilan memiliki satu transaksi dan satu pickup."
            >
                <div className="overflow-x-auto rounded-xl border">
                    <table className={tableClass}>
                        <thead>
                            <tr>
                                <th>Pickup / Rute</th>
                                <th>Driver → Agent</th>
                                <th>Jadwal</th>
                                <th>Volume</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pickups.data.map((pickup) => (
                                <tr key={pickup.pickup_id}>
                                    <td>
                                        <p className="font-mono text-xs">
                                            pkp-
                                            {String(pickup.pickup_id).padStart(
                                                3,
                                                '0',
                                            )}
                                        </p>
                                        {pickup.route_key && (
                                            <p className="mt-1 flex items-center gap-1 text-[10px] text-muted-foreground">
                                                <Route size={11} />
                                                {pickup.route_key.slice(0, 8)} ·
                                                titik {pickup.stop_order}
                                            </p>
                                        )}
                                    </td>
                                    <td>
                                        <p className="text-xs font-semibold">
                                            {pickup.driver?.name} →{' '}
                                            {pickup.agent?.name}
                                        </p>
                                        <p className="mt-1 max-w-xs text-[11px] text-muted-foreground">
                                            {pickup.destination}
                                        </p>
                                    </td>
                                    <td className="text-xs whitespace-nowrap">
                                        {dateLabel(pickup.date)}
                                        <p className="mt-1 text-muted-foreground">
                                            {pickup.time.slice(0, 5)}
                                        </p>
                                    </td>
                                    <td className="font-semibold whitespace-nowrap">
                                        {liter(
                                            pickup.transaction_agent
                                                ?.volume_liter ?? 0,
                                        )}
                                    </td>
                                    <td>
                                        <StatusPill status={pickup.status} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {!pickups.data.length && (
                        <EmptyState
                            title="Belum ada penugasan"
                            description="Buat rute pertama dari Agent yang sudah mencapai batas minimum."
                        />
                    )}
                </div>
                <PaginationNav page={pickups} />
            </Panel>
            <Dialog open={scheduleOpen} onOpenChange={setScheduleOpen}>
                <DialogContent className="kilang-shell max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Buat rute pengambilan</DialogTitle>
                        <DialogDescription>
                            Pilih Driver, tentukan jadwal, lalu susun tujuan
                            Agent.
                        </DialogDescription>
                    </DialogHeader>
                    <ScheduleForm
                        agents={eligibleAgents}
                        drivers={drivers}
                        onDone={() => setScheduleOpen(false)}
                    />
                </DialogContent>
            </Dialog>
        </>
    );
}
