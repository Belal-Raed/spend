import { Head, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import InstallButton from '@/components/install-button';

/** The shell around the sign-in and sign-up screens: the app icon and name above the form. */
export default function AuthCard({ title, children }: { title: string; children: ReactNode }) {
    const { appName } = usePage().props;

    return (
        <main className="mx-auto flex min-h-dvh max-w-sm flex-col justify-center gap-8 px-4 pt-[calc(env(safe-area-inset-top)+2rem)] pb-[calc(env(safe-area-inset-bottom)+2rem)]">
            <Head title={title} />

            <div className="flex flex-col items-center gap-4 text-center">
                <img src="/icons/icon-192.png" alt="" className="size-20 rounded-[22%] shadow-lg shadow-emerald-900/20" />

                <div className="flex flex-col gap-1">
                    <p className="text-[28px] font-bold">{appName}</p>
                    <h1 className="text-[17px] text-muted">{title}</h1>
                </div>
            </div>

            {children}

            <InstallButton variant="link" />
        </main>
    );
}
