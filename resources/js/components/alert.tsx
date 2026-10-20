import type { ReactNode } from 'react';
import Icon from '@/components/icon';

/** A problem the user needs to see, such as a category that could not be deleted. */
export default function Alert({ children }: { children: ReactNode }) {
    return (
        <p role="alert" className="flex items-start gap-2 rounded-xl bg-negative/10 px-4 py-3 text-[15px] text-negative">
            <Icon name="exclamation" className="mt-0.5 size-5" />
            <span>{children}</span>
        </p>
    );
}
