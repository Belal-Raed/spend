import { Link, usePage } from '@inertiajs/react';
import Icon from '@/components/icon';
import { cx } from '@/lib/cx';
import { routes } from '@/lib/routes';

function daysLabel(days: number): string {
    if (days === 1) {
        return 'يوم واحد';
    }

    return days === 2 ? 'يومان' : `${days} أيام`;
}

/**
 * A reminder to subscribe, shown in the last days of a trial or subscription
 * and while the account is read-only. Tapping it opens the subscription page.
 */
export default function AccessBanner() {
    const { access } = usePage().props;

    if (!access || (access.status !== 'read_only' && !access.is_ending_soon)) {
        return null;
    }

    const isReadOnly = access.status === 'read_only';
    const message = isReadOnly
        ? 'حسابك للقراءة فقط. اشترك لتضيف وتعدّل.'
        : `باقي ${daysLabel(access.days_left)} على انتهاء ${access.status === 'trial' ? 'التجربة' : 'اشتراكك'}.`;

    return (
        <Link
            href={routes.subscription()}
            className={cx(
                'flex items-center gap-3 rounded-xl px-4 py-3 text-[15px] active:opacity-70',
                isReadOnly ? 'bg-warning/15 text-label' : 'bg-surface text-label',
            )}
        >
            <Icon name={isReadOnly ? 'exclamation' : 'credit-card'} className={cx('size-6', isReadOnly ? 'text-warning' : 'text-tint')} />
            <span className="flex-1">{message}</span>
            <span className="font-semibold text-tint">اشترك</span>
        </Link>
    );
}
