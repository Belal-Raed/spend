import { Form, usePage } from '@inertiajs/react';
import Amount from '@/components/amount';
import AmountField from '@/components/amount-field';
import Group from '@/components/group';
import Input from '@/components/input';
import Page from '@/components/page';
import { transactionKind } from '@/components/transaction-kind';
import { routes } from '@/lib/routes';
import type { Transaction } from '@/types';

export default function RepaymentsCreate({ debt, remaining }: { debt: Transaction; remaining: string }) {
    const { today } = usePage().props;

    // Cancelling goes back to the debts tab this debt is listed under.
    const cancel = routes.debts.index() + (debt.debt_direction === 'owed_to_me' ? '#owed-to-me' : '');

    return (
        <Page title="دفعة سداد" modal cancel={cancel} submit="repayment-form" submitLabel="تسجيل">
            <Form id="repayment-form" action={routes.debts.repayments.store(debt.id)} method="post" className="flex flex-col gap-7">
                <p className="-mb-4 text-center text-[15px] text-muted">
                    {transactionKind(debt)} · المتبقي <Amount value={remaining} className="font-semibold text-label" />
                </p>

                <AmountField label="مبلغ الدفعة" defaultValue={remaining} max={remaining} autoFocus />

                <Group>
                    <Input name="occurred_on" type="date" label="التاريخ" defaultValue={today} dir="ltr" required />
                    <Input name="note" label="ملاحظة" placeholder="اختياري" />
                </Group>
            </Form>
        </Page>
    );
}
