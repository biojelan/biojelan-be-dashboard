import { Leaf, MapPin, ShieldCheck, Truck } from 'lucide-react';
import type { ReactNode } from 'react';
import { Brand } from '@/components/kilang/common';
export default function KilangLoginLayout({
    children,
}: {
    children: ReactNode;
}) {
    return (
        <div className="kilang-shell flex min-h-screen items-center justify-center bg-background p-5 text-foreground sm:p-10">
            <div className="grid w-full max-w-5xl overflow-hidden rounded-3xl border bg-card shadow-xl lg:grid-cols-[1.05fr_1fr]">
                <aside className="relative hidden flex-col justify-between overflow-hidden bg-[#143329] p-10 text-white lg:flex">
                    <div className="absolute -right-20 -bottom-20 size-100 rounded-full border border-white/10" />
                    <div className="absolute -top-30 -left-20 size-100 rounded-full border border-amber-300/10" />
                    <div className="relative">
                        <Brand light />
                        <div className="mt-20 inline-flex items-center gap-2 rounded-full border border-amber-300/25 bg-amber-300/5 px-3 py-1.5 text-[10px] font-semibold tracking-wider text-amber-200 uppercase">
                            <Leaf size={12} />
                            Circular by nature
                        </div>
                        <h1 className="mt-6 text-4xl leading-tight font-bold tracking-tight">
                            Kelola Kilang.
                            <br />
                            Gerakkan perubahan.
                        </h1>
                        <p className="mt-5 max-w-sm text-sm leading-relaxed text-white/60">
                            Satukan tim, pengambilan minyak, dan laporan Factory
                            dalam workspace BioJelan.
                        </p>
                        <div className="mt-12 grid gap-5">
                            <div className="flex items-center gap-3 text-xs text-white/70">
                                <ShieldCheck
                                    size={18}
                                    className="text-amber-300"
                                />
                                Empat role, akses sesuai tanggung jawab
                            </div>
                            <div className="flex items-center gap-3 text-xs text-white/70">
                                <Truck size={18} className="text-amber-300" />
                                Penugasan dan status Driver terhubung
                            </div>
                            <div className="flex items-center gap-3 text-xs text-white/70">
                                <MapPin size={18} className="text-amber-300" />
                                Dari Agent hingga Factory
                            </div>
                        </div>
                    </div>
                    <p className="relative mt-20 text-[10px] tracking-wide text-white/35">
                        BIOJELAN · KILANG WORKSPACE
                    </p>
                </aside>
                <main className="px-7 py-10 sm:px-12 sm:py-14">
                    <div className="mb-10 lg:hidden">
                        <Brand />
                    </div>
                    <p className="text-[10px] font-bold tracking-[0.2em] text-amber-700 uppercase dark:text-amber-300">
                        Selamat datang kembali
                    </p>
                    <h2 className="mt-3 text-2xl font-bold tracking-tight">
                        Masuk ke workspace Kilang
                    </h2>
                    <p className="mt-3 mb-8 text-xs leading-relaxed text-muted-foreground">
                        Gunakan akun Superadmin, Admin, Manajer Operasional,
                        atau Manajer Finance Anda.
                    </p>
                    {children}
                    <p className="mt-8 border-t pt-5 text-[11px] leading-relaxed text-muted-foreground">
                        Hubungi Superadmin Factory untuk mendapatkan akun Kilang
                        atau bantuan akses.
                    </p>
                </main>
            </div>
        </div>
    );
}
