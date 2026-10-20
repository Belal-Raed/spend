import type { SelectHTMLAttributes } from 'react';
import Icon from '@/components/icon';
import { useFieldError } from '@/lib/use-field-error';

/** A choice as a row of a grouped list: its label at the start and the chosen option at the end. */
export default function Select({
    name,
    label,
    children,
    ...attributes
}: { name: string; label: string } & SelectHTMLAttributes<HTMLSelectElement>) {
    const error = useFieldError(name);

    return (
        <label className="ios-row flex w-full cursor-pointer items-center gap-3 ps-4 transition-colors active:bg-pressed">
            <span className="ios-row-body flex min-h-11 min-w-0 flex-1 flex-col justify-center gap-0.5 py-1.5 pe-4">
                <span className="flex items-center gap-3">
                    <span className="flex-1">{label}</span>

                    <span className="relative flex min-w-0 items-center gap-1 text-muted">
                        <select
                            id={name}
                            name={name}
                            aria-invalid={error ? true : undefined}
                            className="max-w-48 min-w-0 cursor-pointer appearance-none truncate bg-transparent py-1.5 text-end outline-none"
                            {...attributes}
                        >
                            {children}
                        </select>

                        <Icon name="chevron-up-down" className="pointer-events-none size-4" strokeWidth={2} />
                    </span>
                </span>

                {error && <span className="text-[13px] text-negative">{error}</span>}
            </span>
        </label>
    );
}
