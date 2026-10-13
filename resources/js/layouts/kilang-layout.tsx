import { Link, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    Building2,
    ChevronRight,
    CircleDollarSign,
    LayoutDashboard,
    LogOut,
    Menu,
    Moon,
    Sun,
    Settings,
    ShieldCheck,
    Truck,
    Users,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Brand } from '@/components/kilang/common';
import { cn } from '@/lib/utils';
import { useAppearance } from '@/hooks/use-appearance';
import { dashboard, logout } from '@/routes';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Kilang/UserManagementController';
import { index as operationsIndex } from '@/actions/App/Http/Controllers/Kilang/OperationsController';
import { index as financeIndex } from '@/actions/App/Http/Controllers/Kilang/FinanceController';
import { edit as profileEdit } from '@/routes/profile';
import type { KilangShared } from '@/types/kilang';
import { roleLabels } from '@/types/kilang';
export default function KilangLayout({ children }: { children: ReactNode }) {
    const { props, url } = usePage<KilangShared>();
    const { factory, permissions } = props.kilang;
    const user = props.auth.user;
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const [open, setOpen] = useState(false);
    const items = [
        {
            title: 'Ringkasan',
            href: dashboard(),
            icon: LayoutDashboard,
            visible: true,
        },
        {
            title: 'Manajemen User',
            href: usersIndex(),
            icon: Users,
            visible: permissions.users,
        },
        {
            title: 'Operasional',
            href: operationsIndex(),
            icon: Truck,
            visible: permissions.operations,
        },
        {
            title: 'Keuangan',
            href: financeIndex(),
            icon: CircleDollarSign,
            visible: permissions.finance,
        },
    ].filter((item) => item.visible);
    const current = items.find((item) => url.split('?')[0] === item.href.url);
    return (
        <div className="kilang-shell min-h-screen bg-background text-foreground">
            {open && (
                <button
                    className="fixed inset-0 z-30 bg-black/40 lg:hidden"
                    onClick={() => setOpen(false)}
                    aria-label="Tutup navigasi"
                />
            )}
            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-[#143329] text-[#eaf3ec] transition-transform lg:translate-x-0',
                    open ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                <div className="flex items-center justify-between px-6 py-7">
                    <Brand light />
                    <button
                        onClick={() => setOpen(false)}
                        className="lg:hidden"
                        aria-label="Tutup menu"
                    >
                        <X size={20} />
                    </button>
                </div>
                <div className="mx-4 rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                    <p className="text-[9px] font-semibold tracking-[0.15em] text-white/40 uppercase">
                        Workspace Factory
                    </p>
                    <p className="mt-1 flex items-center gap-2 text-xs font-semibold text-amber-100">
                        <Building2 size={14} />
                        {factory?.factory_name ?? 'BioJelan'}
                    </p>
                </div>
                <p className="px-7 pt-8 pb-3 text-[9px] font-bold tracking-[0.2em] text-white/35 uppercase">
                    Manajemen Kilang
                </p>
                <nav className="flex-1 space-y-1 px-3">
                    {items.map((item) => (
                        <Link
                            key={item.title}
                            href={item.href}
                            onClick={() => setOpen(false)}
                            className={cn(
                                'flex items-center gap-3 rounded-xl px-4 py-3 text-[13px] font-semibold transition hover:bg-white/5 hover:text-white',
                                current?.title === item.title
                                    ? 'bg-amber-400/12 text-amber-100'
                                    : 'text-white/60',
                            )}
                        >
                            <item.icon
                                size={18}
                                className={
                                    current?.title === item.title
                                        ? 'text-amber-400'
                                        : ''
                                }
                            />
                            {item.title}
                            {current?.title === item.title && (
                                <ChevronRight className="ml-auto" size={14} />
                            )}
                        </Link>
                    ))}
                </nav>
                <div className="m-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                    <ShieldCheck className="mb-3 text-amber-300" size={22} />
                    <p className="text-xs font-semibold">
                        Akses sesuai tanggung jawab
                    </p>
                    <p className="mt-1 text-[11px] leading-relaxed text-white/45">
                        {roleLabels[Number(user.role_id)] ?? 'Akun Kilang'} ·{' '}
                        {factory?.factory_name}
                    </p>
                </div>
                <div className="border-t border-white/10 p-4">
                    <div className="mb-4 flex items-center gap-3">
                        <div className="flex size-9 items-center justify-center rounded-xl bg-amber-400/20 text-sm font-bold text-amber-200">
                            {user.name.slice(0, 2).toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-xs font-semibold">
                                {user.name}
                            </p>
                            <p className="truncate text-[10px] text-white/45">
                                {user.email}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link
                            href={profileEdit()}
                            className="flex flex-1 items-center justify-center gap-2 rounded-lg bg-white/5 py-2 text-[11px] hover:bg-white/10"
                        >
                            <Settings size={13} />
                            Profil
                        </Link>
                        <Link
                            href={logout()}
                            as="button"
                            className="flex flex-1 items-center justify-center gap-2 rounded-lg bg-white/5 py-2 text-[11px] hover:bg-white/10"
                        >
                            <LogOut size={13} />
                            Keluar
                        </Link>
                    </div>
                </div>
            </aside>
            <div className="lg:pl-64">
                <header className="sticky top-0 z-20 flex h-17 items-center justify-between gap-4 border-b bg-background/95 px-5 backdrop-blur sm:px-8">
                    <div className="flex items-center gap-3">
                        <button
                            className="rounded-xl border p-2.5 lg:hidden"
                            onClick={() => setOpen(true)}
                            aria-label="Buka menu"
                        >
                            <Menu size={18} />
                        </button>
                        <div>
                            <p className="text-[9px] font-semibold tracking-widest text-muted-foreground uppercase">
                                BioJelan / Kilang
                            </p>
                            <p className="text-sm font-semibold">
                                {current?.title ?? 'Dashboard'}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        className="ml-auto rounded-xl border p-2.5 text-muted-foreground hover:bg-muted"
                        onClick={() =>
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        }
                        aria-label={
                            resolvedAppearance === 'dark'
                                ? 'Gunakan tampilan terang'
                                : 'Gunakan tampilan gelap'
                        }
                    >
                        {resolvedAppearance === 'dark' ? (
                            <Sun size={16} />
                        ) : (
                            <Moon size={16} />
                        )}
                    </button>
                    <span className="hidden items-center gap-2 text-xs text-muted-foreground sm:flex">
                        <span className="size-2 rounded-full bg-emerald-500" />
                        {roleLabels[Number(user.role_id)]}
                        <ArrowUpRight size={14} />
                    </span>
                </header>
                <main className="mx-auto max-w-[1500px] p-5 sm:p-8">
                    {props.flash?.success && (
                        <div
                            role="status"
                            className="mb-6 rounded-xl border border-primary/20 bg-primary/8 p-4 text-sm text-primary"
                        >
                            {props.flash.success}
                        </div>
                    )}
                    {props.flash?.error && (
                        <div
                            role="alert"
                            className="mb-6 rounded-xl border border-destructive/20 p-4 text-sm text-destructive"
                        >
                            {props.flash.error}
                        </div>
                    )}
                    {children}
                </main>
                <footer className="flex flex-wrap items-center justify-between gap-2 border-t px-8 py-5 text-[10px] text-muted-foreground">
                    <span>
                        BioJelan · Menghubungkan minyak jelantah dengan masa
                        depan.
                    </span>
                    <span>{factory?.factory_name}</span>
                </footer>
            </div>
        </div>
    );
}
