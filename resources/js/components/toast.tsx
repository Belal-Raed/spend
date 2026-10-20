import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Icon from '@/components/icon';

/**
 * The saved/deleted message the server flashes after a change. It shows for
 * a moment, then fades away. Each new message starts the animation again,
 * even when the same page stays on screen.
 */
export default function Toast() {
    const { flash } = usePage();
    const [toast, setToast] = useState(() => (flash.status ? { id: 0, message: flash.status } : null));

    useEffect(
        () =>
            router.on('flash', (event) => {
                const message = event.detail.flash.status;

                if (message) {
                    setToast({ id: Date.now(), message });
                }
            }),
        [],
    );

    if (!toast) {
        return null;
    }

    return (
        <p
            key={toast.id}
            role="status"
            className="toast pointer-events-none fixed inset-x-0 top-[calc(env(safe-area-inset-top)+0.5rem)] z-40 mx-auto flex w-fit items-center gap-2 rounded-full bg-raised px-5 py-3 text-[15px] font-semibold shadow-xl ring-[0.5px] shadow-black/15 ring-separator"
        >
            <Icon name="check-circle" className="size-5 text-positive" />
            {toast.message}
        </p>
    );
}
