import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Droplets, Inbox } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { Pagination } from '@/types/kilang';
export function Brand({ light = false }: { light?: boolean }) {
    return (
        <div className="flex items-center gap-3">
            <span
                className={cn(
                    'flex size-10 items-center justify-center rounded-xl',
                    light
                        ? 'bg-white/10 text-amber-300'
                        : 'bg-primary text-primary-foreground',
                )}
            >
                <Droplets size={23} />
            </span>
            <div>
                <div className="text-lg font-bold tracking-tight">
                    BioJelan<span className="text-amber-500">.</span>
                </div>
                <div
                    className={cn(
                        'text-[10px] font-semibold tracking-[0.2em] uppercase',
                        light ? 'text-white/50' : 'text-muted-foreground',
                    )}
                >
                    Kilang workspace
                </div>
            </div>
        </div>
    );
}
export function PageHeading({
    eyebrow,
    title,
    description,
    action,
}: {
    eyebrow: string;
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <div className="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p className="mb-2 text-[11px] font-bold tracking-[0.18em] text-amber-700 uppercase dark:text-amber-400">
                    {eyebrow}
                </p>
                <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                    {title}
                </h1>
                <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
                    {description}
                </p>
            </div>
            {action}
        </div>
    );
}
export function Panel({
    title,
    description,
    children,
    action,
    className,
}: {
    title?: string;
    description?: string;
    children: ReactNode;
    action?: ReactNode;
    className?: string;
}) {
    return (
        <section
            className={cn(
                'rounded-2xl border bg-card p-5 shadow-sm sm:p-6',
                className,
            )}
        >
            {title && (
                <div className="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 className="font-semibold">{title}</h2>
                        {description && (
                            <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {action}
                </div>
            )}
            {children}
        </section>
    );
}
export function StatCard({
    label,
    value,
    detail,
    icon: Icon,
}: {
    label: string;
    value: string | number;
    detail: string;
    icon: LucideIcon;
}) {
    return (
        <Panel className="relative overflow-hidden">
            <div className="mb-4 flex items-center justify-between gap-3">
                <span className="text-xs font-semibold text-muted-foreground">
                    {label}
                </span>
                <span className="rounded-xl bg-primary/8 p-2.5 text-primary">
                    <Icon size={19} />
                </span>
            </div>
            <p className="text-2xl font-bold tracking-tight sm:text-3xl">
                {value}
            </p>
            <p className="mt-2 text-xs text-muted-foreground">{detail}</p>
        </Panel>
    );
}
const statusLabels: Record<string, string> = {
    ACTIVE: 'Aktif',
    INACTIVE: 'Nonaktif',
    ASSIGNED: 'Ditugaskan',
    OTW: 'Dalam perjalanan',
    ARRIVED: 'Tiba di Agent',
    COMPLETED: 'Selesai',
    CANCELLED: 'Dibatalkan',
    PENDING: 'Menunggu',
    ACCEPTED: 'Diterima',
    REJECTED: 'Ditolak',
    CANCEL_REQUESTED: 'Permintaan batal',
    APPROVED: 'Disetujui',
};
export function StatusPill({ status }: { status: string }) {
    const variant = ['COMPLETED', 'ACCEPTED', 'APPROVED', 'ACTIVE'].includes(
        status,
    )
        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
        : ['CANCELLED', 'REJECTED', 'INACTIVE'].includes(status)
          ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
          : ['OTW', 'ARRIVED'].includes(status)
            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'
            : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap',
                variant,
            )}
        >
            <span className="size-1.5 rounded-full bg-current" />
            {statusLabels[status] ?? status}
        </span>
    );
}
export function ErrorList({
    errors,
}: {
    errors: Record<string, string | undefined>;
}) {
    const messages = Object.entries(errors).filter(([, value]) => value);
    return (
        messages.length > 0 && (
            <div
                role="alert"
                className="rounded-xl border border-destructive/25 bg-destructive/5 p-3 text-sm text-destructive"
            >
                <ul className="list-inside list-disc space-y-1">
                    {messages.map(([key, value]) => (
                        <li key={key}>{value}</li>
                    ))}
                </ul>
            </div>
        )
    );
}
export function EmptyState({
    title = 'Belum ada data',
    description = 'Data akan muncul di sini setelah aktivitas pertama.',
}: {
    title?: string;
    description?: string;
}) {
    return (
        <div className="flex flex-col items-center gap-2 px-4 py-12 text-center">
            <Inbox className="mb-1 text-muted-foreground/50" size={32} />
            <h3 className="text-sm font-semibold">{title}</h3>
            <p className="max-w-md text-xs text-muted-foreground">
                {description}
            </p>
        </div>
    );
}
export function PaginationNav({
    page,
}: {
    page: Omit<Pagination<never>, 'data'>;
}) {
    return (
        <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
            <p className="text-xs text-muted-foreground">
                {page.total} data · halaman {page.current_page} dari{' '}
                {page.last_page}
            </p>
            <nav aria-label="Pagination" className="flex flex-wrap gap-1">
                {page.links.map((link, i) =>
                    link.url ? (
                        <Link
                            key={i}
                            href={link.url}
                            preserveScroll
                            className={cn(
                                'rounded-lg border px-3 py-1.5 text-xs',
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'bg-card hover:bg-muted',
                            )}
                        >
                            {i === 0
                                ? 'Sebelumnya'
                                : i === page.links.length - 1
                                  ? 'Berikutnya'
                                  : link.label.replace(/&[^;]+;/g, '')}
                        </Link>
                    ) : null,
                )}
            </nav>
        </div>
    );
}
export const fieldClass =
    'h-10 w-full rounded-xl border bg-background px-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 disabled:opacity-50';
export const tableClass =
    'w-full text-left text-sm [&_th]:bg-muted/50 [&_th]:px-4 [&_th]:py-3 [&_th]:text-[10px] [&_th]:font-semibold [&_th]:tracking-wider [&_th]:text-muted-foreground [&_th]:uppercase [&_td]:border-t [&_td]:px-4 [&_td]:py-4';
