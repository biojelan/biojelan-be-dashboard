import { Head, useForm } from '@inertiajs/react';
import {
    ArrowDownRight,
    ArrowUpRight,
    Building2,
    Download,
    Plus,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    index,
    pdf,
    price as savePrice,
    store,
} from '@/actions/App/Http/Controllers/Kilang/FinanceController';
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
    FinanceRow,
    Inventory,
    Pagination,
    Report,
    TransactionRow,
} from '@/types/kilang';
import { dateLabel, liter, localToday, rupiah } from '@/types/kilang';
type Price = {
    price_id: number;
    price_type: string;
    price_per_liter: string;
    start_date: string;
};
type Props = {
    report: Report;
    allTimeReport: Report;
    inventory: Inventory;
    clientTransactions: Pagination<TransactionRow>;
    agentTransactions: Pagination<TransactionRow>;
    entries: Pagination<FinanceRow>;
    prices: (Price | null)[];
    filters: { from: string; to: string; status: string };
};
function PriceEditor({
    value,
    type,
}: {
    value: Price | null;
    type: 'CLIENT' | 'AGENT';
}) {
    const form = useForm({
        price_type: type,
        price_per_liter: value?.price_per_liter ?? '',
    });
    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(savePrice.url(), { preserveScroll: true });
    }
    return (
        <form onSubmit={submit} className="space-y-4 rounded-xl border p-4">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm font-semibold">
                        {type === 'CLIENT'
                            ? 'Client → Agent'
                            : 'Agent → Factory'}
                    </p>
                    <p className="mt-1 text-[11px] text-muted-foreground">
                        {type === 'CLIENT'
                            ? 'Harga pembelian oleh Agent'
                            : 'Harga pembelian melalui Driver'}
                    </p>
                </div>
                <span className="rounded-lg bg-primary/8 px-2 py-1 text-[10px] font-semibold text-primary">
                    {type}
                </span>
            </div>
            <p className="text-xl font-bold">
                {value ? rupiah(value.price_per_liter) : 'Belum diatur'}{' '}
                <span className="text-xs font-normal text-muted-foreground">
                    / liter
                </span>
            </p>
            <ErrorList errors={form.errors} />
            <div className="flex gap-2">
                <input
                    aria-label={`Harga ${type} per liter`}
                    className={fieldClass}
                    type="number"
                    min="0.01"
                    step="0.01"
                    required
                    value={form.data.price_per_liter}
                    onChange={(event) =>
                        form.setData('price_per_liter', event.target.value)
                    }
                />
                <Button
                    variant="outline"
                    type="submit"
                    disabled={form.processing}
                >
                    Simpan
                </Button>
            </div>
            {value && (
                <p className="text-[10px] text-muted-foreground">
                    Berlaku sejak {dateLabel(value.start_date)}
                </p>
            )}
        </form>
    );
}
function EntryForm({
    type,
    inventory,
    onDone,
}: {
    type: string;
    inventory: Inventory;
    onDone: () => void;
}) {
    const form = useForm({
        type,
        date: localToday(),
        amount: '',
        volume_liter: '',
        description: '',
    });
    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(store.url(), { preserveScroll: true, onSuccess: onDone });
    }
    return (
        <form onSubmit={submit} className="space-y-5">
            <ErrorList errors={form.errors} />
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="entry-date">Tanggal</Label>
                    <input
                        id="entry-date"
                        type="date"
                        max={localToday()}
                        required
                        className={fieldClass}
                        value={form.data.date}
                        onChange={(event) =>
                            form.setData('date', event.target.value)
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="entry-amount">
                        Total {type === 'SALE' ? 'penjualan' : 'biaya'} (Rp)
                    </Label>
                    <input
                        id="entry-amount"
                        type="number"
                        min="0.01"
                        step="0.01"
                        required
                        className={fieldClass}
                        value={form.data.amount}
                        onChange={(event) =>
                            form.setData('amount', event.target.value)
                        }
                    />
                </div>
                {type === 'SALE' && (
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="entry-volume">Minyak terjual (L)</Label>
                        <input
                            id="entry-volume"
                            type="number"
                            step="0.001"
                            min="0.001"
                            max={inventory.stock_liter}
                            required
                            className={fieldClass}
                            value={form.data.volume_liter}
                            onChange={(event) =>
                                form.setData('volume_liter', event.target.value)
                            }
                        />
                        <p className="text-xs text-muted-foreground">
                            Stok Factory tersedia {liter(inventory.stock_liter)}
                            .
                        </p>
                    </div>
                )}
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="entry-description">
                        Keterangan / referensi
                    </Label>
                    <textarea
                        id="entry-description"
                        required
                        maxLength={1000}
                        className="min-h-24 rounded-xl border bg-background p-3 text-sm"
                        placeholder={
                            type === 'SALE'
                                ? 'Pembeli, nomor invoice, atau detail penjualan'
                                : 'Bahan bakar, transportasi, atau biaya operasional'
                        }
                        value={form.data.description}
                        onChange={(event) =>
                            form.setData('description', event.target.value)
                        }
                    />
                </div>
            </div>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onDone}>
                    Batal
                </Button>
                <Button type="submit" disabled={form.processing}>
                    Simpan catatan
                </Button>
            </div>
        </form>
    );
}
function TransactionTable({
    page,
    type,
}: {
    page: Pagination<TransactionRow>;
    type: 'client' | 'agent';
}) {
    return (
        <>
            <div className="overflow-x-auto rounded-xl border">
                <table className={tableClass}>
                    <thead>
                        <tr>
                            <th>Transaksi</th>
                            <th>
                                {type === 'client'
                                    ? 'Client → Agent'
                                    : 'Driver / Agent'}
                            </th>
                            <th>Volume</th>
                            <th>Harga / L</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {page.data.map((item) => (
                            <tr key={item.id}>
                                <td>
                                    <p className="font-mono text-xs whitespace-nowrap">
                                        {item.transaction_id}
                                    </p>
                                    <p className="mt-1 text-[10px] text-muted-foreground">
                                        {dateLabel(item.created_at)}
                                    </p>
                                </td>
                                <td>
                                    <p className="text-xs font-semibold whitespace-nowrap">
                                        {type === 'client'
                                            ? (item.client?.name ??
                                              'Guest Client')
                                            : item.driver?.name}
                                    </p>
                                    <p className="mt-1 text-[11px] text-muted-foreground">
                                        Agent: {item.agent.name}
                                    </p>
                                </td>
                                <td className="text-xs font-semibold whitespace-nowrap">
                                    {liter(item.volume_liter)}
                                </td>
                                <td className="text-xs whitespace-nowrap">
                                    {rupiah(item.price.price_per_liter)}
                                </td>
                                <td className="text-xs font-semibold whitespace-nowrap">
                                    {rupiah(item.total_price)}
                                </td>
                                <td>
                                    <StatusPill status={item.status} />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {!page.data.length && (
                    <EmptyState title="Tidak ada transaksi pada periode ini" />
                )}
            </div>
            <PaginationNav page={page} />
        </>
    );
}
export default function Finance({
    report,
    allTimeReport,
    inventory,
    clientTransactions,
    agentTransactions,
    entries,
    prices,
    filters,
}: Props) {
    const [entryType, setEntryType] = useState<string | null>(null);
    const [tab, setTab] = useState<'ledger' | 'client' | 'agent'>('ledger');
    const form = useForm({
        from: filters.from,
        to: filters.to,
        status: filters.status,
    });
    const scale = Math.max(report.income, report.total_expense, 1);
    return (
        <>
            <Head title="Keuangan Kilang" />
            <PageHeading
                eyebrow="Finance & accountability"
                title="Manajemen Keuangan"
                description="Riwayat transaksi, harga pembelian, catatan penjualan Factory, dan laporan laba/rugi berbasis kas."
                action={
                    <a
                        href={pdf.url({
                            query: { from: filters.from, to: filters.to },
                        })}
                        className="inline-flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground"
                    >
                        <Download size={15} />
                        Unduh laporan PDF
                    </a>
                }
            />
            <Panel className="mb-6">
                <form
                    className="flex flex-wrap items-end gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.get(index.url(), { preserveState: true });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="finance-from">Dari tanggal</Label>
                        <input
                            id="finance-from"
                            type="date"
                            required
                            className={fieldClass}
                            value={form.data.from}
                            onChange={(event) =>
                                form.setData('from', event.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="finance-to">Sampai tanggal</Label>
                        <input
                            id="finance-to"
                            type="date"
                            min={form.data.from}
                            max={localToday()}
                            required
                            className={fieldClass}
                            value={form.data.to}
                            onChange={(event) =>
                                form.setData('to', event.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="finance-status">Status transaksi</Label>
                        <select
                            id="finance-status"
                            className={fieldClass}
                            value={form.data.status}
                            onChange={(event) =>
                                form.setData('status', event.target.value)
                            }
                        >
                            <option value="">Semua status</option>
                            {[
                                'PENDING',
                                'ACCEPTED',
                                'REJECTED',
                                'CANCEL_REQUESTED',
                                'CANCELLED',
                            ].map((status) => (
                                <option key={status} value={status}>
                                    {status}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="outline">
                        Terapkan
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => {
                            form.setData('from', '2020-01-01');
                        }}
                    >
                        Sejak awal
                    </Button>
                </form>
                <div className="mt-3">
                    <ErrorList errors={form.errors} />
                </div>
            </Panel>
            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Pemasukan periode"
                    value={rupiah(report.income)}
                    detail="Penjualan minyak Factory"
                    icon={ArrowUpRight}
                />
                <StatCard
                    label="Pengeluaran periode"
                    value={rupiah(report.total_expense)}
                    detail="Pembelian minyak + biaya operasional"
                    icon={ArrowDownRight}
                />
                <StatCard
                    label={
                        report.profit >= 0
                            ? 'Laba berbasis kas'
                            : 'Rugi berbasis kas'
                    }
                    value={rupiah(Math.abs(report.profit))}
                    detail="Pemasukan dikurangi pengeluaran"
                    icon={TrendingUp}
                />
                <StatCard
                    label="Minyak diterima periode"
                    value={liter(report.received_liter)}
                    detail="Pickup selesai pada periode terpilih"
                    icon={Building2}
                />
            </div>
            <div className="mb-6 grid gap-5 xl:grid-cols-[1fr_1fr]">
                <Panel
                    title="Ringkasan Factory"
                    description="Angka sepanjang waktu hingga hari ini."
                >
                    <div className="grid grid-cols-2 gap-4 border-b pb-5">
                        <div>
                            <p className="text-[11px] text-muted-foreground">
                                Total pemasukan
                            </p>
                            <p className="mt-2 text-xl font-bold">
                                {rupiah(allTimeReport.income)}
                            </p>
                        </div>
                        <div>
                            <p className="text-[11px] text-muted-foreground">
                                Total minyak terkumpul
                            </p>
                            <p className="mt-2 text-xl font-bold">
                                {liter(inventory.received_liter)}
                            </p>
                        </div>
                        <div>
                            <p className="text-[11px] text-muted-foreground">
                                Stok Factory saat ini
                            </p>
                            <p className="mt-2 text-lg font-semibold">
                                {liter(inventory.stock_liter)}
                            </p>
                        </div>
                        <div>
                            <p className="text-[11px] text-muted-foreground">
                                Minyak terjual
                            </p>
                            <p className="mt-2 text-lg font-semibold">
                                {liter(inventory.sold_liter)}
                            </p>
                        </div>
                    </div>
                    <div className="mt-5 space-y-4">
                        {[
                            {
                                label: 'Pemasukan periode',
                                value: report.income,
                                color: 'bg-emerald-600',
                            },
                            {
                                label: 'Pengeluaran periode',
                                value: report.total_expense,
                                color: 'bg-amber-500',
                            },
                        ].map((item) => (
                            <div key={item.label}>
                                <div className="mb-2 flex justify-between text-xs">
                                    <span className="text-muted-foreground">
                                        {item.label}
                                    </span>
                                    <span className="font-semibold">
                                        {rupiah(item.value)}
                                    </span>
                                </div>
                                <div className="h-2 rounded-full bg-muted">
                                    <div
                                        className={`h-full rounded-full ${item.color}`}
                                        style={{
                                            width: `${(item.value / scale) * 100}%`,
                                        }}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                    <p className="mt-5 text-[11px] leading-relaxed text-muted-foreground">
                        Biaya pembelian dihitung saat minyak diterima melalui
                        pickup COMPLETED. Transaksi Client–Agent ditampilkan
                        sebagai riwayat jaringan dan tidak dihitung sebagai
                        biaya kedua bagi Factory. Laporan ini berbasis kas,
                        belum memperhitungkan penilaian persediaan, pajak, dan
                        penyusutan.
                    </p>
                </Panel>
                <Panel
                    title="Harga minyak per liter"
                    description="Perubahan membuat harga baru; nilai transaksi lama tetap tersimpan."
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <PriceEditor
                            key={
                                prices.find(
                                    (value) => value?.price_type === 'CLIENT',
                                )?.price_id ?? 'client'
                            }
                            value={
                                prices.find(
                                    (value) => value?.price_type === 'CLIENT',
                                ) ?? null
                            }
                            type="CLIENT"
                        />
                        <PriceEditor
                            key={
                                prices.find(
                                    (value) => value?.price_type === 'AGENT',
                                )?.price_id ?? 'agent'
                            }
                            value={
                                prices.find(
                                    (value) => value?.price_type === 'AGENT',
                                ) ?? null
                            }
                            type="AGENT"
                        />
                    </div>
                </Panel>
            </div>
            <Panel
                title="Catatan dan riwayat transaksi"
                description="Catat pemasukan serta biaya, atau periksa dua alur pembelian dalam jaringan."
                action={
                    <div className="flex flex-wrap gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setEntryType('EXPENSE')}
                        >
                            <Plus size={14} />
                            Catat biaya
                        </Button>
                        <Button
                            size="sm"
                            onClick={() => setEntryType('SALE')}
                            disabled={inventory.stock_liter <= 0}
                        >
                            <Plus size={14} />
                            Catat penjualan
                        </Button>
                    </div>
                }
            >
                <div className="mb-5 flex flex-wrap gap-2 rounded-xl bg-muted/70 p-1.5">
                    {(
                        [
                            { key: 'ledger', label: 'Penjualan & biaya' },
                            { key: 'client', label: 'Client–Agent' },
                            { key: 'agent', label: 'Driver–Agent' },
                        ] as const
                    ).map((item) => (
                        <button
                            type="button"
                            key={item.key}
                            onClick={() => setTab(item.key)}
                            className={`rounded-lg px-4 py-2 text-xs font-semibold transition ${tab === item.key ? 'bg-card text-primary shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            {item.label}
                        </button>
                    ))}
                </div>
                {tab === 'ledger' && (
                    <>
                        <div className="overflow-x-auto rounded-xl border">
                            <table className={tableClass}>
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>Keterangan</th>
                                        <th>Volume</th>
                                        <th>Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {entries.data.map((entry) => (
                                        <tr key={entry.id}>
                                            <td className="text-xs whitespace-nowrap">
                                                {dateLabel(entry.date)}
                                            </td>
                                            <td>
                                                <span
                                                    className={`rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap ${entry.type === 'SALE' ? 'bg-primary/10 text-primary' : 'bg-amber-100 text-amber-800'}`}
                                                >
                                                    {entry.type === 'SALE'
                                                        ? 'Penjualan'
                                                        : 'Biaya'}
                                                </span>
                                            </td>
                                            <td className="max-w-sm text-xs">
                                                {entry.description}
                                            </td>
                                            <td className="text-xs whitespace-nowrap">
                                                {entry.volume_liter
                                                    ? liter(entry.volume_liter)
                                                    : '—'}
                                            </td>
                                            <td className="text-xs font-semibold whitespace-nowrap">
                                                {rupiah(entry.amount)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {!entries.data.length && (
                                <EmptyState
                                    title="Belum ada catatan keuangan"
                                    description="Catat penjualan dan biaya operasional untuk melengkapi laporan."
                                />
                            )}
                        </div>
                        <PaginationNav page={entries} />
                    </>
                )}
                {tab === 'client' && (
                    <TransactionTable page={clientTransactions} type="client" />
                )}
                {tab === 'agent' && (
                    <TransactionTable page={agentTransactions} type="agent" />
                )}
            </Panel>
            <Dialog
                open={entryType !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEntryType(null);
                    }
                }}
            >
                <DialogContent className="kilang-shell">
                    <DialogHeader>
                        <DialogTitle>
                            {entryType === 'SALE'
                                ? 'Catat penjualan minyak'
                                : 'Catat biaya operasional'}
                        </DialogTitle>
                        <DialogDescription>
                            {entryType === 'SALE'
                                ? 'Penjualan mengurangi stok Factory dan menambah pemasukan.'
                                : 'Catatan biaya ditambahkan ke pengeluaran laporan.'}
                        </DialogDescription>
                    </DialogHeader>
                    {entryType && (
                        <EntryForm
                            key={entryType}
                            type={entryType}
                            inventory={inventory}
                            onDone={() => setEntryType(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
