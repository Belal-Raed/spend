import { Link, router, usePage } from '@inertiajs/react';
import Group from '@/components/group';
import Icon from '@/components/icon';
import Page from '@/components/page';
import Select from '@/components/select';
import TransactionItem from '@/components/transaction-item';
import { cx } from '@/lib/cx';
import { dayTitle } from '@/lib/format';
import { routes } from '@/lib/routes';
import type { Category, Option, Transaction } from '@/types';

type Paginated<Item> = {
    data: Item[];
    links: { prev: string | null; next: string | null };
};

/**
 * The transactions list's address with the given filters. Empty filters are left out.
 */
function filtered(period: string | null, category: number | null): string {
    const query = new URLSearchParams();

    if (period) {
        query.set('period', period);
    }

    if (category) {
        query.set('category', String(category));
    }

    const queryString = query.toString();

    return routes.transactions.index() + (queryString ? `?${queryString}` : '');
}

export default function TransactionsIndex({
    transactions,
    period,
    periods,
    category,
    categoryGroups,
}: {
    transactions: Paginated<Transaction>;
    period: string | null;
    periods: Option[];
    category: number | null;
    categoryGroups: { label: string; categories: Category[] }[];
}) {
    const { today } = usePage().props;
    const periodLinks: { label: string; value: string | null }[] = [{ label: 'الكل', value: null }, ...periods];

    // Newest first, so each day's transactions are already next to each other.
    const days = new Map<string, Transaction[]>();

    for (const transaction of transactions.data) {
        days.set(transaction.occurred_on, [...(days.get(transaction.occurred_on) ?? []), transaction]);
    }

    return (
        <Page title="الحركات">
            <div className="flex flex-col gap-4">
                <nav className="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4" aria-label="الفترة">
                    {periodLinks.map((option) => (
                        <Link
                            key={option.label}
                            href={filtered(option.value, category)}
                            preserveScroll
                            className={cx(
                                'shrink-0 rounded-full px-4 py-1.5 text-[15px] font-medium whitespace-nowrap transition active:opacity-60',
                                option.value === period ? 'bg-emerald-600 text-white' : 'bg-surface text-label',
                            )}
                            aria-current={option.value === period ? 'true' : undefined}
                        >
                            {option.label}
                        </Link>
                    ))}
                </nav>

                <Group>
                    <Select
                        name="category"
                        label="التصنيف"
                        value={category ?? ''}
                        onChange={(event) => router.get(filtered(period, Number(event.target.value) || null), {}, { preserveScroll: true })}
                    >
                        <option value="">كل التصنيفات</option>
                        {categoryGroups.map((group) => (
                            <optgroup key={group.label} label={group.label}>
                                {group.categories.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.name}
                                    </option>
                                ))}
                            </optgroup>
                        ))}
                    </Select>
                </Group>
            </div>

            {days.size > 0 ? (
                [...days].map(([date, transactionsOfDay]) => (
                    <Group key={date} title={dayTitle(date, today)}>
                        {transactionsOfDay.map((transaction) => (
                            <TransactionItem key={transaction.id} transaction={transaction} />
                        ))}
                    </Group>
                ))
            ) : (
                <div className="flex flex-col items-center gap-2 py-16 text-center">
                    <Icon name="list" className="size-12 text-faint" />
                    <p className="text-[17px] font-semibold">لا توجد حركات.</p>
                    <p className="text-[15px] text-muted">اضغط + فوق لتسجيل أول حركة.</p>
                </div>
            )}

            {(transactions.links.prev || transactions.links.next) && (
                <nav className="grid grid-cols-2 gap-3" aria-label="الصفحات">
                    {transactions.links.prev ? (
                        <Link href={transactions.links.prev} className="rounded-xl bg-surface py-3 text-center text-tint active:bg-pressed">
                            الأحدث
                        </Link>
                    ) : (
                        <span className="rounded-xl bg-surface py-3 text-center text-faint">الأحدث</span>
                    )}

                    {transactions.links.next ? (
                        <Link href={transactions.links.next} prefetch className="rounded-xl bg-surface py-3 text-center text-tint active:bg-pressed">
                            الأقدم
                        </Link>
                    ) : (
                        <span className="rounded-xl bg-surface py-3 text-center text-faint">الأقدم</span>
                    )}
                </nav>
            )}
        </Page>
    );
}
