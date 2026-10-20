import { cx } from '@/lib/cx';
import { formatAmount } from '@/lib/format';

/** Numbers stay left-to-right so a minus sign is not moved to the wrong side in the RTL page. */
export default function Amount({ value, className }: { value: number | string; className?: string }) {
    return (
        <span dir="ltr" className={cx('tabular-nums', className)}>
            {formatAmount(value)}
        </span>
    );
}
