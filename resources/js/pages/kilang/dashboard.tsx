import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CircleDollarSign,
    Droplets,
    FileCheck2,
    Leaf,
    Truck,
    Users,
} from 'lucide-react';
import { PageHeading, Panel, StatCard } from '@/components/kilang/common';
import { index as userIndex } from '@/actions/App/Http/Controllers/Kilang/UserManagementController';
import { index as operationsIndex } from '@/actions/App/Http/Controllers/Kilang/OperationsController';
import { index as financeIndex } from '@/actions/App/Http/Controllers/Kilang/FinanceController';
import type { Inventory, KilangShared, Report } from '@/types/kilang';
import { liter, rupiah } from '@/types/kilang';
type Props = {
    stats: {
        users: number | null;
        pending_requests: number | null;
        agent_stock: number | null;
        active_pickups: number | null;
        inventory: Inventory | null;
        finance: Report | null;
    };
};
export default function Dashboard({ stats }: Props) {
    const { kilang, auth } = usePage<KilangShared>().props;
    return (
        <>
            <Head title="Ringkasan Kilang" />
            <PageHeading
                eyebrow="Workspace overview"
                title={`Selamat datang, ${auth.user.name.split(' ')[0]}.`}
                description="Pantau aktivitas jaringan BioJelan dan kelola pekerjaan Kilang dari satu tempat."
            />
            <section className="relative mb-7 overflow-hidden rounded-3xl bg-[#1f4d3a] p-7 text-white sm:p-9">
                <div className="absolute -top-20 -right-10 size-72 rounded-full border border-white/10" />
                <div className="absolute -right-4 -bottom-30 size-72 rounded-full border border-amber-300/20" />
                <Leaf className="absolute right-12 bottom-6 hidden size-24 text-white/10 sm:block" />
                <div className="relative max-w-xl">
                    <span className="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-[10px] font-semibold tracking-wider text-amber-200 uppercase">
                        <Building2 size={13} />
                        {kilang.factory?.factory_name}
                    </span>
                    <h2 className="text-2xl leading-tight font-bold tracking-tight sm:text-3xl">
                        Setiap liter punya
                        <br />
                        perjalanan yang berarti.
                    </h2>
                    <p className="mt-3 max-w-md text-xs leading-relaxed text-white/65">
                        Dari Client, dikumpulkan Agent, diangkut Driver, lalu
                        diterima Factory. Kelola setiap tahap dengan catatan
                        yang saling terhubung.
                    </p>
                </div>
            </section>
            <div
                className={`mb-7 grid gap-4 sm:grid-cols-2 ${stats.finance && stats.pending_requests !== null ? 'xl:grid-cols-5' : stats.finance ? 'xl:grid-cols-4' : 'xl:grid-cols-3'}`}
            >
                {stats.inventory && (
                    <StatCard
                        icon={Building2}
                        label="Stok Factory"
                        value={liter(stats.inventory.stock_liter)}
                        detail="Diterima dikurangi minyak terjual"
                    />
                )}
                {stats.agent_stock !== null && (
                    <StatCard
                        icon={Droplets}
                        label="Stok seluruh Agent"
                        value={liter(stats.agent_stock)}
                        detail="Stok tersedia pada Agent aktif"
                    />
                )}
                {stats.active_pickups !== null && (
                    <StatCard
                        icon={Truck}
                        label="Pickup aktif"
                        value={stats.active_pickups}
                        detail="Ditugaskan, berjalan, atau tiba"
                    />
                )}
                {stats.pending_requests !== null && (
                    <StatCard
                        icon={FileCheck2}
                        label="Permintaan Agent"
                        value={stats.pending_requests}
                        detail="Menunggu peninjauan Admin"
                    />
                )}
                {stats.finance && (
                    <StatCard
                        icon={CircleDollarSign}
                        label="Pemasukan sepanjang waktu"
                        value={rupiah(stats.finance.income)}
                        detail="Catatan penjualan Factory hingga hari ini"
                    />
                )}
            </div>
            <div className="grid gap-5 xl:grid-cols-3">
                {kilang.permissions.users && (
                    <Panel
                        title="Manajemen User"
                        description={`${stats.users ?? 0} akun dalam jaringan BioJelan`}
                    >
                        <Users size={28} className="mb-5 text-primary" />
                        <p className="mb-5 text-sm leading-relaxed text-muted-foreground">
                            Kelola akun, lengkapi profil, dan tinjau pengajuan
                            Client untuk menjadi Agent.
                        </p>
                        <Link
                            href={userIndex()}
                            className="inline-flex items-center gap-2 text-sm font-semibold text-primary"
                        >
                            Kelola akun <ArrowRight size={15} />
                        </Link>
                    </Panel>
                )}
                {kilang.permissions.operations && (
                    <Panel
                        title="Manajemen Operasional"
                        description={`Minimum pengambilan ${liter(kilang.factory?.minimum_pickup_liter ?? 50)}`}
                    >
                        <Truck size={28} className="mb-5 text-primary" />
                        <p className="mb-5 text-sm leading-relaxed text-muted-foreground">
                            Susun tujuan pengambilan, tugaskan Driver, dan
                            pantau stok serta status pickup.
                        </p>
                        <Link
                            href={operationsIndex()}
                            className="inline-flex items-center gap-2 text-sm font-semibold text-primary"
                        >
                            Lihat operasional <ArrowRight size={15} />
                        </Link>
                    </Panel>
                )}
                {kilang.permissions.finance && (
                    <Panel
                        title="Manajemen Keuangan"
                        description="Harga, transaksi, dan laporan Factory"
                    >
                        <CircleDollarSign
                            size={28}
                            className="mb-5 text-primary"
                        />
                        <p className="mb-5 text-sm leading-relaxed text-muted-foreground">
                            Catat penjualan dan biaya, periksa riwayat
                            transaksi, lalu unduh laporan PDF.
                        </p>
                        <Link
                            href={financeIndex()}
                            className="inline-flex items-center gap-2 text-sm font-semibold text-primary"
                        >
                            Lihat keuangan <ArrowRight size={15} />
                        </Link>
                    </Panel>
                )}
            </div>
        </>
    );
}
