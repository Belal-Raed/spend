/**
 * Joins class names, skipping the ones whose condition is false.
 */
export function cx(...classes: (string | false | null | undefined)[]): string {
    return classes.filter(Boolean).join(' ');
}
