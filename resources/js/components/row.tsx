import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Icon, { type IconName } from '@/components/icon';
import { cx } from '@/lib/cx';

/**
 * One row of a grouped list. It is a link when given `href`, a button when
 * given `onClick`, and plain text otherwise. A link with `download` fetches a
 * file instead of visiting a page. `icon` draws a coloured square at the
 * start, and `detail` sits at the end, before the chevron that links show.
 */
export default function Row({
    href,
    download = false,
    onClick,
    icon,
    tone = 'bg-tint',
    chevron,
    destructive = false,
    detail,
    children,
}: {
    href?: string;
    download?: boolean;
    onClick?: () => void;
    icon?: IconName;
    tone?: string;
    chevron?: boolean;
    destructive?: boolean;
    detail?: ReactNode;
    children: ReactNode;
}) {
    const isTappable = href !== undefined || onClick !== undefined;
    const showsChevron = chevron ?? href !== undefined;

    const className = cx(
        'ios-row flex w-full items-center gap-3 ps-4 text-start',
        isTappable && 'cursor-pointer transition-colors active:bg-pressed',
        destructive && 'text-negative',
    );

    const content = (
        <>
            {icon && (
                <span className={cx('flex size-[30px] shrink-0 items-center justify-center rounded-[7px] text-white', tone)}>
                    <Icon name={icon} className="size-[18px]" strokeWidth={2} />
                </span>
            )}

            <span className="ios-row-body flex min-h-11 min-w-0 flex-1 items-center gap-3 py-2.5 pe-4">
                <span className="flex min-w-0 flex-1 flex-col">{children}</span>

                {detail}

                {showsChevron && <Icon name="chevron-left" className="size-4 text-faint" strokeWidth={2.5} />}
            </span>
        </>
    );

    if (href !== undefined && download) {
        return (
            <a href={href} download className={className}>
                {content}
            </a>
        );
    }

    if (href !== undefined) {
        return (
            <Link href={href} prefetch className={className}>
                {content}
            </Link>
        );
    }

    if (onClick !== undefined) {
        return (
            <button type="button" onClick={onClick} className={className}>
                {content}
            </button>
        );
    }

    return <div className={className}>{content}</div>;
}
