import type { InputHTMLAttributes } from 'react';
import { cx } from '@/lib/cx';
import { useFieldError } from '@/lib/use-field-error';

/**
 * A text field as a row of a grouped list: its label at the start and the
 * field filling the rest. A date shows as a small grey pill at the end of
 * the row instead, as on iOS.
 */
export default function Input({
    name,
    label,
    type = 'text',
    className,
    ...attributes
}: { name: string; label: string } & InputHTMLAttributes<HTMLInputElement>) {
    const error = useFieldError(name);

    // The date field is left-to-right inside the right-to-left page, so its
    // push to the end of the row is a physical left margin, not ms-auto.
    const fieldClasses =
        type === 'date'
            ? 'mr-auto cursor-pointer rounded-lg bg-fill px-2.5 py-1 tabular-nums outline-none'
            : 'min-w-0 flex-1 bg-transparent py-1.5 outline-none placeholder:text-faint';

    return (
        <label className="ios-row flex w-full items-center gap-3 ps-4">
            <span className="ios-row-body flex min-h-11 min-w-0 flex-1 flex-col justify-center gap-0.5 py-1.5 pe-4">
                <span className="flex items-center gap-3">
                    <span className="w-28 shrink-0">{label}</span>

                    <input id={name} name={name} type={type} aria-invalid={error ? true : undefined} className={cx(fieldClasses, className)} {...attributes} />
                </span>

                {error && <span className="text-[13px] text-negative">{error}</span>}
            </span>
        </label>
    );
}
