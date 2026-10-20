import { useFieldError } from '@/lib/use-field-error';

/** The amount of a transaction, typed in large digits in its own block. */
export default function AmountField({
    name = 'amount',
    label = 'المبلغ',
    defaultValue,
    max,
    autoFocus = false,
}: {
    name?: string;
    label?: string;
    defaultValue?: string;
    max?: string;
    autoFocus?: boolean;
}) {
    const error = useFieldError(name);

    return (
        <section className="flex flex-col items-center gap-1 rounded-xl bg-surface px-4 py-5">
            <label htmlFor={name} className="text-[13px] text-muted">
                {label}
            </label>

            <input
                id={name}
                name={name}
                type="number"
                defaultValue={defaultValue}
                step="0.01"
                min="0.01"
                max={max}
                inputMode="decimal"
                dir="ltr"
                placeholder="0.00"
                required
                autoFocus={autoFocus}
                aria-invalid={error ? true : undefined}
                className="w-full [appearance:textfield] bg-transparent text-center text-5xl font-bold tabular-nums outline-none placeholder:text-faint [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
            />

            {error && <p className="text-[13px] text-negative">{error}</p>}
        </section>
    );
}
