import { usePage } from '@inertiajs/react';

/**
 * The validation message the server sent back for a field, if any.
 */
export function useFieldError(name: string): string | undefined {
    return usePage().props.errors[name];
}
