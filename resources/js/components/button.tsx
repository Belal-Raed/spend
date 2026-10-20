import type { ButtonHTMLAttributes } from 'react';
import { cx } from '@/lib/cx';

const variants = {
    // A deeper green than the tint in both themes, so white text on it stays easy to read.
    primary: 'bg-emerald-600 text-white',
    secondary: 'bg-fill text-tint',
};

export default function Button({
    variant = 'primary',
    type = 'submit',
    className,
    ...attributes
}: { variant?: keyof typeof variants } & ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            type={type}
            className={cx(
                'inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl px-5 py-3.5 text-[17px] font-semibold transition select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-tint focus-visible:ring-offset-2 focus-visible:ring-offset-canvas active:opacity-70 disabled:opacity-50',
                variants[variant],
                className,
            )}
            {...attributes}
        />
    );
}
