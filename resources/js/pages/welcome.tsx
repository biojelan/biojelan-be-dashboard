import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CircleDollarSign,
    Droplets,
    Leaf,
    ShieldCheck,
    Truck,
    Users,
} from 'lucide-react';
import { Brand } from '@/components/kilang/common';
import { dashboard, login, logout } from '@/routes';
export default function Welcome() {
    const { auth } = usePage().props;
    const staff = auth.user && [1, 2, 3, 4].includes(Number(auth.user.role_id));
    return (
        <div className="kilang-shell min-h-screen bg-background text-foreground">
            <Head title="BioJelan Kilang" />
            <header className="mx-auto flex max-w-6xl items-center justify-between p-6 sm:p-8">
                <Brand />
                {staff ? (
                    <Link
                        href={dashboard()}
                        className="text-sm font-semibold text-primary"
                    >
                        Buka dashboard{' '}
                        <ArrowRight className="inline" size={15} />
                    </Link>
                ) : auth.user ? (
                    <Link
                        href={logout()}
                        as="button"
                        className="text-sm font-semibold text-primary"
                    >
                        Keluar
                    </Link>
                ) : (
                    <Link
                        href={login()}
                        className="text-sm font-semibold text-primary"
                    >
                        Masuk <ArrowRight className="inline" size={15} />
                    </Link>
                )}
            </header>
            <main className="mx-auto max-w-6xl px-6 pt-12 pb-16 sm:px-8">
                <div className="relative overflow-hidden rounded-[32px] bg-[#143329] p-8 text-white sm:p-14">
                    <div className="absolute -top-24 -right-16 size-100 rounded-full border border-white/10" />
                    <Leaf className="absolute right-14 bottom-12 hidden size-44 text-white/8 md:block" />
                    <div className="relative max-w-2xl">
                        <span className="inline-flex items-center gap-2 rounded-full border border-amber-300/20 bg-amber-300/5 px-3 py-1.5 text-[10px] font-semibold tracking-widest text-amber-200 uppercase">
                            <Leaf size={13} />
                            BioJelan · Kilang workspace
                        </span>
                        <h1 className="mt-8 text-4xl leading-tight font-bold tracking-tight sm:text-5xl">
                            Satu workspace.
                            <br />
                            Setiap liter terhubung.
                        </h1>
                        <p className="mt-5 max-w-lg text-sm leading-relaxed text-white/60">
                            Kelola jaringan pengguna, rute pengambilan minyak
                            jelantah, persediaan Factory, dan laporan keuangan
                            BioJelan.
                        </p>
                        <Link
                            href={staff ? dashboard() : login()}
                            className="mt-8 inline-flex items-center gap-2 rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-[#143329]"
                        >
                            {staff ? 'Buka dashboard' : 'Masuk ke dashboard'}
                            <ArrowRight size={17} />
                        </Link>
                    </div>
                </div>
                <div className="mt-6 grid gap-4 md:grid-cols-3">
                    {[
                        {
                            icon: Users,
                            title: 'Tim & jaringan',
                            detail: 'Akun, profil, dan peninjauan permintaan Agent.',
                        },
                        {
                            icon: Truck,
                            title: 'Pengambilan & stok',
                            detail: 'Penugasan Driver dan persediaan minyak yang terhubung.',
                        },
                        {
                            icon: CircleDollarSign,
                            title: 'Keuangan & laporan',
                            detail: 'Harga transaksi, catatan keuangan, serta laporan PDF.',
                        },
                    ].map((item) => (
                        <div
                            key={item.title}
                            className="rounded-2xl border bg-card p-6"
                        >
                            <item.icon
                                className="mb-5 text-primary"
                                size={25}
                            />
                            <h2 className="font-semibold">{item.title}</h2>
                            <p className="mt-2 text-xs leading-relaxed text-muted-foreground">
                                {item.detail}
                            </p>
                        </div>
                    ))}
                </div>
                <div className="mt-12 flex flex-wrap items-center justify-center gap-5 text-xs font-semibold text-muted-foreground">
                    <span className="flex items-center gap-2">
                        <Droplets size={16} />
                        Client
                    </span>
                    <ArrowRight size={15} />
                    <span className="flex items-center gap-2">
                        <Users size={16} />
                        Agent
                    </span>
                    <ArrowRight size={15} />
                    <span className="flex items-center gap-2">
                        <Truck size={16} />
                        Driver
                    </span>
                    <ArrowRight size={15} />
                    <span className="flex items-center gap-2">
                        <Building2 size={16} />
                        Factory
                    </span>
                </div>
                <p className="mt-10 flex items-center justify-center gap-2 text-[11px] text-muted-foreground">
                    <ShieldCheck size={14} />
                    Akses hanya untuk akun Kilang yang terhubung ke Factory.
                </p>
            </main>
        </div>
    );
}
