import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Check,
    Pencil,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    index,
    store,
    update,
    destroy,
    review,
} from '@/actions/App/Http/Controllers/Kilang/UserManagementController';
import {
    EmptyState,
    ErrorList,
    fieldClass,
    PageHeading,
    PaginationNav,
    Panel,
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
import type { KilangShared, Pagination, Person } from '@/types/kilang';
import { roleLabels } from '@/types/kilang';
type ProfileData = {
    address: string;
    latitude: string;
    longitude: string;
    bank_name: string;
    account_number: string;
    open_at: string;
    close_at: string;
    open_days: string[];
    plate_number: string;
    type_vehicle: string;
};
type UserData = {
    name: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
    role_id: number;
    is_active: boolean;
    profile: ProfileData;
};
type Application = {
    id: number;
    user: Person;
    profile: ProfileData;
    status: string;
    created_at: string;
};
const weekdays = [
    'senin',
    'selasa',
    'rabu',
    'kamis',
    'jumat',
    'sabtu',
    'minggu',
];
function UserForm({
    user,
    onDone,
}: {
    user: Person | null;
    onDone: () => void;
}) {
    const actor = usePage().props.auth.user;
    const form = useForm<UserData>({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone: user?.phone ?? '',
        password: '',
        password_confirmation: '',
        role_id: user?.role_id ?? 7,
        is_active: user?.is_active ?? true,
        profile: {
            address: user?.agen?.address ?? '',
            latitude: user?.agen?.latitude ?? '',
            longitude: user?.agen?.longitude ?? '',
            bank_name: user?.agen?.bank_name ?? '',
            account_number: user?.agen?.account_number ?? '',
            open_at: user?.agen?.open_at.slice(0, 5) ?? '08:00',
            close_at: user?.agen?.close_at.slice(0, 5) ?? '17:00',
            open_days: user?.agen?.open_days ?? weekdays.slice(0, 5),
            plate_number: user?.driver?.plate_number ?? '',
            type_vehicle: user?.driver?.type_vehicle ?? '',
        },
    });
    const roles = user
        ? user.role_id === null
            ? [7]
            : user.role_id === 7
              ? [7, 6]
              : [user.role_id]
        : Number(actor.role_id) === 1
          ? [7, 6, 5, 4, 3, 2]
          : [7, 6, 5, 4, 3];
    function submit(event: FormEvent) {
        event.preventDefault();
        if (user) {
            form.patch(update.url(user.id), { onSuccess: onDone });
        } else {
            form.post(store.url(), { onSuccess: onDone });
        }
    }
    const profileField = (
        key: keyof Omit<ProfileData, 'open_days'>,
        label: string,
        type = 'text',
    ) => (
        <div className="grid gap-2">
            <Label htmlFor={`profile-${key}`}>{label}</Label>
            <input
                id={`profile-${key}`}
                required
                className={fieldClass}
                type={type}
                value={form.data.profile[key]}
                onChange={(event) =>
                    form.setData('profile', {
                        ...form.data.profile,
                        [key]: event.target.value,
                    })
                }
            />
        </div>
    );
    return (
        <form onSubmit={submit} className="space-y-5">
            <ErrorList errors={form.errors} />
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="user-name">Nama lengkap</Label>
                    <input
                        id="user-name"
                        required
                        className={fieldClass}
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="user-role">Role</Label>
                    <select
                        id="user-role"
                        className={fieldClass}
                        value={form.data.role_id}
                        onChange={(event) =>
                            form.setData('role_id', Number(event.target.value))
                        }
                    >
                        {roles.map((role) => (
                            <option key={role} value={role}>
                                {roleLabels[role]}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="user-email">Email</Label>
                    <input
                        id="user-email"
                        type="email"
                        required
                        className={fieldClass}
                        value={form.data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="user-phone">Nomor telepon</Label>
                    <input
                        id="user-phone"
                        type="tel"
                        className={fieldClass}
                        value={form.data.phone}
                        onChange={(event) =>
                            form.setData('phone', event.target.value)
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="user-password">
                        {user ? 'Password baru (opsional)' : 'Password'}
                    </Label>
                    <input
                        id="user-password"
                        type="password"
                        autoComplete="new-password"
                        minLength={8}
                        required={!user}
                        className={fieldClass}
                        value={form.data.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="user-password-confirmation">
                        Konfirmasi password
                    </Label>
                    <input
                        id="user-password-confirmation"
                        type="password"
                        autoComplete="new-password"
                        className={fieldClass}
                        value={form.data.password_confirmation}
                        onChange={(event) =>
                            form.setData(
                                'password_confirmation',
                                event.target.value,
                            )
                        }
                    />
                </div>
            </div>
            {form.data.role_id === 6 && (
                <fieldset className="rounded-xl border bg-muted/30 p-4">
                    <legend className="px-2 text-xs font-semibold">
                        Profil Agent
                    </legend>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {profileField('address', 'Alamat')}
                        {profileField('bank_name', 'Nama bank')}
                        {profileField('latitude', 'Latitude', 'text')}
                        {profileField('longitude', 'Longitude', 'text')}
                        {profileField('account_number', 'Nomor rekening')}
                        {profileField('open_at', 'Jam buka', 'time')}
                        {profileField('close_at', 'Jam tutup', 'time')}
                        <div className="sm:col-span-2">
                            <Label>Hari buka</Label>
                            <div className="mt-3 flex flex-wrap gap-3">
                                {weekdays.map((day) => (
                                    <label
                                        key={day}
                                        className="flex items-center gap-2 text-xs capitalize"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={form.data.profile.open_days.includes(
                                                day,
                                            )}
                                            onChange={(event) =>
                                                form.setData('profile', {
                                                    ...form.data.profile,
                                                    open_days: event.target
                                                        .checked
                                                        ? [
                                                              ...form.data
                                                                  .profile
                                                                  .open_days,
                                                              day,
                                                          ]
                                                        : form.data.profile.open_days.filter(
                                                              (value) =>
                                                                  value !== day,
                                                          ),
                                                })
                                            }
                                        />
                                        {day}
                                    </label>
                                ))}
                            </div>
                        </div>
                    </div>
                </fieldset>
            )}
            {form.data.role_id === 5 && (
                <fieldset className="rounded-xl border bg-muted/30 p-4">
                    <legend className="px-2 text-xs font-semibold">
                        Kendaraan Driver
                    </legend>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {profileField('plate_number', 'Nomor plat')}
                        {profileField('type_vehicle', 'Tipe kendaraan')}
                    </div>
                </fieldset>
            )}
            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={form.data.is_active}
                    disabled={user?.id === actor.id}
                    onChange={(event) =>
                        form.setData('is_active', event.target.checked)
                    }
                />
                Akun aktif
            </label>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onDone}>
                    Batal
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Menyimpan…' : 'Simpan akun'}
                </Button>
            </div>
        </form>
    );
}
function ReviewForm({
    application,
    onDone,
}: {
    application: Application;
    onDone: () => void;
}) {
    const form = useForm({ decision: 'APPROVED', review_note: '' });
    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(review.url(application.id), { onSuccess: onDone });
    }
    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="rounded-xl border bg-muted/40 p-4 text-sm">
                <p className="font-semibold">{application.user.name}</p>
                <p className="text-xs text-muted-foreground">
                    {application.user.email}
                </p>
                <p className="mt-3">{application.profile.address}</p>
                <p className="mt-1 text-xs text-muted-foreground">
                    {application.profile.latitude},{' '}
                    {application.profile.longitude}
                </p>
                <p className="mt-3 text-xs">
                    {application.profile.bank_name} ·{' '}
                    {application.profile.account_number}
                </p>
                <p className="mt-2 text-xs">
                    {application.profile.open_at}–{application.profile.close_at}{' '}
                    · {application.profile.open_days.join(', ')}
                </p>
            </div>
            <ErrorList errors={form.errors} />
            <div className="grid gap-2">
                <Label htmlFor="review-decision">Keputusan</Label>
                <select
                    id="review-decision"
                    className={fieldClass}
                    value={form.data.decision}
                    onChange={(event) =>
                        form.setData('decision', event.target.value)
                    }
                >
                    <option value="APPROVED">Setujui menjadi Agent</option>
                    <option value="REJECTED">Tolak permintaan</option>
                </select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="review-note">
                    Catatan{' '}
                    {form.data.decision === 'REJECTED'
                        ? '(wajib)'
                        : '(opsional)'}
                </Label>
                <textarea
                    id="review-note"
                    className="min-h-24 rounded-xl border bg-background p-3 text-sm"
                    required={form.data.decision === 'REJECTED'}
                    value={form.data.review_note}
                    onChange={(event) =>
                        form.setData('review_note', event.target.value)
                    }
                />
            </div>
            <Button disabled={form.processing} type="submit" className="w-full">
                Simpan keputusan
            </Button>
        </form>
    );
}
export default function Users({
    users,
    applications,
    filters,
}: {
    users: Pagination<Person>;
    applications: Application[];
    filters: { search?: string; role?: number };
}) {
    const { auth } = usePage<KilangShared>().props;
    const [dialog, setDialog] = useState<'create' | Person | null>(null);
    const [application, setApplication] = useState<Application | null>(null);
    const filterForm = useForm({
        search: filters.search ?? '',
        role: filters.role?.toString() ?? '',
    });
    const [actionErrors, setActionErrors] = useState<Record<string, string>>(
        {},
    );
    const canManage = (user: Person) =>
        Number(auth.user.role_id) === 1 ||
        ![1, 2].includes(Number(user.role_id));
    function remove(user: Person) {
        if (
            !window.confirm(
                `Hapus akun ${user.name}? Akun dengan riwayat transaksi harus dinonaktifkan.`,
            )
        ) {
            return;
        }
        router.delete(destroy.url(user.id), {
            preserveScroll: true,
            onError: setActionErrors,
        });
    }
    return (
        <>
            <Head title="Manajemen User" />
            <PageHeading
                eyebrow="People & partners"
                title="Manajemen User"
                description="Kelola tim Kilang dan akun jaringan. Profil Agent serta Driver terhubung langsung dengan akun pengguna."
                action={
                    <Button onClick={() => setDialog('create')}>
                        <Plus size={16} />
                        Tambah akun
                    </Button>
                }
            />
            <Panel
                title="Permintaan menjadi Agent"
                description="Tinjau profil calon Agent sebelum mengubah role Client."
                action={
                    <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                        {applications.length} menunggu
                    </span>
                }
                className="mb-6"
            >
                {applications.length ? (
                    <div className="grid gap-3 md:grid-cols-2">
                        {applications.map((item) => (
                            <div
                                key={item.id}
                                className="flex flex-wrap items-center justify-between gap-4 rounded-xl border p-4"
                            >
                                <div>
                                    <p className="text-sm font-semibold">
                                        {item.user.name}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {item.profile.address}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {item.user.email}
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setApplication(item)}
                                >
                                    <ShieldCheck size={14} />
                                    Tinjau
                                </Button>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="flex items-center gap-3 rounded-xl bg-primary/5 p-4">
                        <Check size={20} className="text-primary" />
                        <p className="text-xs text-muted-foreground">
                            Semua permintaan telah ditinjau.
                        </p>
                    </div>
                )}
            </Panel>
            <Panel
                title="Direktori akun"
                description="Admin tambahan dapat dibuat hanya oleh Superadmin."
            >
                <form
                    className="mb-5 flex flex-wrap gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        filterForm.get(index.url(), { preserveState: true });
                    }}
                >
                    <div className="relative min-w-50 flex-1">
                        <Search
                            className="absolute top-3 left-3 text-muted-foreground"
                            size={15}
                        />
                        <input
                            aria-label="Cari akun"
                            className={`${fieldClass} pl-9`}
                            placeholder="Cari nama atau email…"
                            value={filterForm.data.search}
                            onChange={(event) =>
                                filterForm.setData('search', event.target.value)
                            }
                        />
                    </div>
                    <select
                        aria-label="Filter role"
                        className={`${fieldClass} w-auto`}
                        value={filterForm.data.role}
                        onChange={(event) =>
                            filterForm.setData('role', event.target.value)
                        }
                    >
                        <option value="">Semua role</option>
                        {Object.entries(roleLabels).map(([id, label]) => (
                            <option key={id} value={id}>
                                {label}
                            </option>
                        ))}
                    </select>
                    <Button variant="outline" type="submit">
                        Terapkan
                    </Button>
                </form>
                <ErrorList errors={actionErrors} />
                <div className="overflow-x-auto rounded-xl border">
                    <table className={tableClass}>
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Role</th>
                                <th>Telepon</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map((user) => (
                                <tr key={user.id}>
                                    <td>
                                        <div className="flex items-center gap-3">
                                            <span className="flex size-9 items-center justify-center rounded-xl bg-primary/8 text-primary">
                                                <UserRound size={17} />
                                            </span>
                                            <div>
                                                <p className="font-semibold whitespace-nowrap">
                                                    {user.name}
                                                </p>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {user.email}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span className="rounded-lg bg-muted px-2 py-1 text-xs whitespace-nowrap">
                                            {roleLabels[Number(user.role_id)] ??
                                                'Belum ditetapkan'}
                                        </span>
                                    </td>
                                    <td className="text-xs whitespace-nowrap">
                                        {user.phone ?? '—'}
                                    </td>
                                    <td>
                                        <StatusPill
                                            status={
                                                user.is_active
                                                    ? 'ACTIVE'
                                                    : 'INACTIVE'
                                            }
                                        />
                                    </td>
                                    <td>
                                        <div className="flex gap-2">
                                            {canManage(user) && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        setDialog(user)
                                                    }
                                                >
                                                    <Pencil size={13} />
                                                    {user.role_id === 7
                                                        ? 'Edit / jadikan Agent'
                                                        : 'Edit'}
                                                </Button>
                                            )}
                                            {canManage(user) &&
                                                user.role_id !== 1 &&
                                                user.id !== auth.user.id && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="text-destructive"
                                                        onClick={() =>
                                                            remove(user)
                                                        }
                                                        aria-label={`Hapus ${user.name}`}
                                                    >
                                                        <Trash2 size={13} />
                                                    </Button>
                                                )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {!users.data.length && (
                        <EmptyState
                            title="Akun tidak ditemukan"
                            description="Coba kata kunci atau filter role lainnya."
                        />
                    )}
                </div>
                <PaginationNav page={users} />
            </Panel>
            <Dialog
                open={dialog !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
            >
                <DialogContent className="kilang-shell max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {dialog === 'create'
                                ? 'Tambah akun baru'
                                : 'Edit akun'}
                        </DialogTitle>
                        <DialogDescription>
                            Isi informasi akun dan profil yang diperlukan sesuai
                            role.
                        </DialogDescription>
                    </DialogHeader>
                    {dialog !== null && (
                        <UserForm
                            key={typeof dialog === 'string' ? 'new' : dialog.id}
                            user={typeof dialog === 'string' ? null : dialog}
                            onDone={() => setDialog(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
            <Dialog
                open={application !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setApplication(null);
                    }
                }}
            >
                <DialogContent className="kilang-shell max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Tinjau permintaan Agent</DialogTitle>
                        <DialogDescription>
                            Persetujuan membuat profil Agent dengan stok awal 0
                            L.
                        </DialogDescription>
                    </DialogHeader>
                    {application && (
                        <ReviewForm
                            key={application.id}
                            application={application}
                            onDone={() => setApplication(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
