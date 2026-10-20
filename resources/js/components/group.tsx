import type { ReactNode } from 'react';

/** A rounded block of rows with an optional heading and note, like the iOS Settings app. */
export default function Group({
    title,
    action,
    footer,
    children,
}: {
    title?: string | null;
    /** Sits at the end of the heading, such as a «عرض الكل» link. */
    action?: ReactNode;
    footer?: string | null;
    children: ReactNode;
}) {
    return (
        <section className="flex flex-col gap-1.5">
            {(title || action) && (
                <div className="flex items-end justify-between gap-4 px-4">
                    {title && <h2 className="text-[13px] text-muted">{title}</h2>}
                    {action}
                </div>
            )}

            <div className="overflow-hidden rounded-xl bg-surface">{children}</div>

            {footer && <p className="px-4 text-[13px] text-muted">{footer}</p>}
        </section>
    );
}
