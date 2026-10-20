import Amount from '@/components/amount';
import type { IconName } from '@/components/icon';
import Row from '@/components/row';
import { transactionKind } from '@/components/transaction-kind';
import { cx } from '@/lib/cx';
import { routes } from '@/lib/routes';
import type { Transaction } from '@/types';

/** A transaction as a row of a grouped list. Tapping it opens the transaction for editing. */
export default function TransactionItem({ transaction, showDate = false }: { transaction: Transaction; showDate?: boolean }) {
    const addsToBalance = transaction.adds_to_balance;

    const [icon, tone]: [IconName, string] =
        transaction.is_debt || transaction.is_repayment
            ? ['exchange', 'bg-warning']
            : addsToBalance
              ? ['arrow-down', 'bg-positive']
              : ['arrow-up', 'bg-negative'];

    const details = [
        showDate ? transaction.occurred_on : null,
        transaction.is_debt && transaction.is_settled ? 'انسدّ' : null,
        transaction.note,
    ].filter(Boolean);

    return (
        <Row
            href={routes.transactions.edit(transaction.id)}
            icon={icon}
            tone={tone}
            chevron={false}
            detail={
                <Amount
                    value={addsToBalance ? transaction.amount : -Number(transaction.amount)}
                    className={cx('shrink-0 font-semibold', addsToBalance && 'text-positive')}
                />
            }
        >
            <span className="truncate">{transactionKind(transaction)}</span>

            {details.length > 0 && <span className="truncate text-[13px] text-muted">{details.join(' · ')}</span>}
        </Row>
    );
}
