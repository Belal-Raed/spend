import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { ActionSheet, SheetAction } from '@/components/action-sheet';
import Group from '@/components/group';
import Page from '@/components/page';
import Row from '@/components/row';
import TransactionFields from '@/components/transaction-fields';
import { transactionKind } from '@/components/transaction-kind';
import { routes } from '@/lib/routes';
import type { Category, Transaction } from '@/types';

/**
 * Who a transaction is with, labelled for its kind, or null when it carries no counterparty.
 */
function counterpartyLabel(transaction: Transaction): string | null {
    if (transaction.is_debt) {
        return 'مع مين';
    }

    return transaction.type === 'income' && !transaction.is_repayment ? 'من وين' : null;
}

export default function TransactionsEdit({ transaction, categories }: { transaction: Transaction; categories: Category[] }) {
    const [isDeleteSheetOpen, setIsDeleteSheetOpen] = useState(false);

    return (
        <Page title="تعديل الحركة" modal cancel={routes.transactions.index()} submit="transaction-form">
            <Form id="transaction-form" action={routes.transactions.update(transaction.id)} method="put" className="flex flex-col gap-7">
                <p className="-mb-4 text-center text-[15px] text-muted">{transactionKind(transaction)}</p>

                <TransactionFields
                    transaction={transaction}
                    categories={categories}
                    counterpartyLabel={counterpartyLabel(transaction)}
                    counterpartyPlaceholder={transaction.is_debt ? 'اسم الشخص' : 'اختياري'}
                />
            </Form>

            <Group>
                <Row onClick={() => setIsDeleteSheetOpen(true)} destructive chevron={false}>
                    <span className="text-center">حذف الحركة</span>
                </Row>
            </Group>

            <ActionSheet
                open={isDeleteSheetOpen}
                onClose={() => setIsDeleteSheetOpen(false)}
                message={transaction.is_debt ? 'ستُحذف معه كل دفعات السداد المسجّلة عليه.' : 'لا يمكن التراجع عن الحذف.'}
            >
                <SheetAction action={routes.transactions.destroy(transaction.id)} method="delete" destructive>
                    حذف الحركة
                </SheetAction>
            </ActionSheet>
        </Page>
    );
}
