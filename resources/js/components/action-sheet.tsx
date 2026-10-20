import { Link, router } from '@inertiajs/react';
import { createContext, useContext, useEffect, useRef, type ReactNode } from 'react';
import type { Method } from '@inertiajs/core';
import { cx } from '@/lib/cx';

const CloseSheet = createContext<() => void>(() => {});

/**
 * An iOS action sheet: a list of choices that slides up from the bottom, with
 * a cancel button under it. Tapping the dim area closes it too. The children
 * are <SheetAction> choices.
 */
export function ActionSheet({
    open,
    onClose,
    title,
    message,
    children,
}: {
    open: boolean;
    onClose: () => void;
    title?: string | null;
    message?: string | null;
    children: ReactNode;
}) {
    const dialog = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        if (open && !dialog.current?.open) {
            dialog.current?.showModal();
        } else if (!open && dialog.current?.open) {
            dialog.current.close();
        }
    }, [open]);

    return (
        <dialog
            ref={dialog}
            className="sheet"
            aria-label={title ?? message ?? undefined}
            onClose={onClose}
            onClick={(event) => event.target === event.currentTarget && onClose()}
        >
            <CloseSheet.Provider value={onClose}>
                <div className="flex flex-col gap-2">
                    <div className="flex flex-col overflow-hidden rounded-[14px] bg-raised/95 text-center backdrop-blur-xl [&>*+*]:border-t-[0.5px] [&>*+*]:border-separator">
                        {(title || message) && (
                            <div className="flex flex-col gap-0.5 px-4 py-3.5">
                                {title && <p className="text-[13px] font-semibold text-muted">{title}</p>}
                                {message && <p className="text-[13px] text-muted">{message}</p>}
                            </div>
                        )}

                        {children}
                    </div>

                    <button type="button" onClick={onClose} className="cursor-pointer rounded-[14px] bg-raised py-4 text-[19px] font-semibold text-tint active:bg-pressed">
                        إلغاء
                    </button>
                </div>
            </CloseSheet.Provider>
        </dialog>
    );
}

/**
 * One choice in an action sheet. It is a link when given `href`, and
 * otherwise sends `method` to `action`. Either way the sheet closes.
 */
export function SheetAction({
    href,
    action,
    method = 'post',
    destructive = false,
    children,
}: {
    href?: string;
    action?: string;
    method?: Method;
    destructive?: boolean;
    children: ReactNode;
}) {
    const close = useContext(CloseSheet);
    const className = cx('block w-full cursor-pointer py-4 text-[19px] active:bg-pressed', destructive ? 'text-negative' : 'text-tint');

    if (href) {
        return (
            <Link href={href} className={className} onClick={close}>
                {children}
            </Link>
        );
    }

    return (
        <button
            type="button"
            className={className}
            onClick={() => {
                close();
                router.visit(action!, { method });
            }}
        >
            {children}
        </button>
    );
}
