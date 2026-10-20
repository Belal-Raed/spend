import { Link } from '@inertiajs/react';
import type { Option } from '@/types';
import { cx } from '@/lib/cx';
import { useFieldError } from '@/lib/use-field-error';

const segment = 'flex min-h-8 cursor-pointer items-center justify-center rounded-[7px] px-2 text-center text-[13px] font-semibold transition';
const selected = 'bg-segment shadow-sm shadow-black/10';
const track = 'grid auto-cols-fr grid-flow-col gap-0.5 rounded-[9px] bg-fill p-0.5 select-none';

/**
 * An iOS segmented control made of radio buttons, for a form. Pass
 * `defaultValue` to let the form own the choice, or `value` and `onChange`
 * to control it. `title` shows a small heading above it.
 */
export function Segmented<Value extends string>({
    name,
    options,
    label,
    title,
    defaultValue,
    value,
    onChange,
}: {
    name: string;
    options: Option<Value>[];
    label: string;
    title?: string;
    defaultValue?: Value;
    value?: Value;
    onChange?: (value: Value) => void;
}) {
    const error = useFieldError(name);

    return (
        <div className="flex flex-col gap-1.5">
            {title && (
                <p className="px-4 text-[13px] text-muted" aria-hidden="true">
                    {title}
                </p>
            )}

            <div className={track} role="radiogroup" aria-label={label}>
                {options.map((option) => (
                    <label
                        key={option.value}
                        className={cx(segment, 'has-checked:bg-segment has-checked:shadow-sm has-checked:shadow-black/10 has-focus-visible:ring-2 has-focus-visible:ring-tint')}
                    >
                        <input
                            type="radio"
                            name={name}
                            value={option.value}
                            className="sr-only"
                            required
                            {...(value === undefined
                                ? { defaultChecked: option.value === defaultValue }
                                : { checked: option.value === value, onChange: () => onChange?.(option.value) })}
                        />
                        {option.label}
                    </label>
                ))}
            </div>

            {error && <p className="px-4 text-[13px] text-negative">{error}</p>}
        </div>
    );
}

/**
 * An iOS segmented control that switches between pages.
 */
export function SegmentedLinks({ links, label }: { links: { label: string; href: string; isActive: boolean }[]; label: string }) {
    return (
        <nav className={track} aria-label={label}>
            {links.map((link) => (
                <Link
                    key={link.href}
                    href={link.href}
                    replace
                    prefetch
                    className={cx(segment, link.isActive && selected)}
                    aria-current={link.isActive ? 'page' : undefined}
                >
                    {link.label}
                </Link>
            ))}
        </nav>
    );
}
