import type { Transaction } from '@/types';

/**
 * What a transaction is, in a few words: its type, and its category or the person it is with.
 */
export function transactionKind(transaction: Transaction): string {
    if (transaction.is_debt) {
        return `دين ${transaction.debt_direction_label} · ${transaction.counterparty}`;
    }

    if (transaction.is_repayment) {
        return `سداد دين · ${transaction.counterparty}`;
    }

    return [transaction.type_label, transaction.category ?? 'بدون تصنيف', transaction.counterparty].filter(Boolean).join(' · ');
}
