const amountFormat = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/**
 * An amount with two decimals and thousands separators, such as 1,250.00.
 */
export function formatAmount(value: number | string): string {
    return amountFormat.format(Number(value));
}

/**
 * The day before a Y-m-d date, as Y-m-d.
 */
export function previousDay(date: string): string {
    const day = new Date(`${date}T00:00:00Z`);
    day.setUTCDate(day.getUTCDate() - 1);

    return day.toISOString().slice(0, 10);
}

/**
 * A day's heading in a list: «اليوم», «أمس», or the weekday and date, with
 * the year only when it is not this year.
 */
export function dayTitle(date: string, today: string): string {
    if (date === today) {
        return 'اليوم';
    }

    if (date === previousDay(today)) {
        return 'أمس';
    }

    return new Intl.DateTimeFormat('ar-u-nu-latn', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: date.slice(0, 4) === today.slice(0, 4) ? undefined : 'numeric',
        timeZone: 'UTC',
    })
        .format(new Date(`${date}T00:00:00Z`))
        .replace('،', '');
}
