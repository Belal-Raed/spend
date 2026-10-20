import { useState } from 'react';
import { ActionSheet, SheetAction } from '@/components/action-sheet';
import Amount from '@/components/amount';
import Group from '@/components/group';
import Row from '@/components/row';
import { formatAmount } from '@/lib/format';
import { routes } from '@/lib/routes';
import type { Debt } from '@/types';

/**
 * Open debts as a grouped list, each with what is left on it. With `actions`,
 * tapping a debt opens a sheet to settle it, pay part of it, or edit it;
 * without, it links to the debts screen.
 */
export default function DebtList({ title, debts, empty, actions = false }: { title?: string; debts: Debt[]; empty: string; actions?: boolean }) {
    const [selectedDebt, setSelectedDebt] = useState<Debt | null>(null);

    // Summed in cents so the total is exact.
    const remainingInCents = debts.reduce((total, debt) => total + Math.round(debt.remaining * 100), 0);

    return (
        <>
            <Group title={title} footer={debts.length > 0 ? `المجموع المتبقي: ${formatAmount(remainingInCents / 100)}` : null}>
                {debts.length > 0 ? (
                    debts.map((debt) => {
                        const percentRepaid = Math.round((debt.repaid * 100) / debt.amount);

                        return (
                            <Row
                                key={debt.id}
                                href={actions ? undefined : routes.debts.index()}
                                onClick={actions ? () => setSelectedDebt(debt) : undefined}
                                chevron
                                detail={
                                    <span className="flex shrink-0 flex-col items-end">
                                        <Amount value={debt.remaining} className="font-semibold" />

                                        {debt.repaid > 0 && (
                                            <span className="text-[13px] text-muted">
                                                من أصل <Amount value={debt.amount} />
                                            </span>
                                        )}
                                    </span>
                                }
                            >
                                <span className="truncate font-medium">{debt.counterparty}</span>

                                <span className="truncate text-[13px] text-muted">
                                    {debt.occurred_on}
                                    {debt.note && ` · ${debt.note}`}
                                </span>

                                {debt.repaid > 0 && (
                                    <span
                                        className="mt-2 h-1 overflow-hidden rounded-full bg-fill"
                                        role="progressbar"
                                        aria-label="نسبة المسدَّد"
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        aria-valuenow={percentRepaid}
                                    >
                                        <span className="block h-full rounded-full bg-positive" style={{ width: `${percentRepaid}%` }} />
                                    </span>
                                )}
                            </Row>
                        );
                    })
                ) : (
                    <Row>
                        <span className="text-muted">{empty}</span>
                    </Row>
                )}
            </Group>

            {actions && (
                <ActionSheet
                    open={selectedDebt !== null}
                    onClose={() => setSelectedDebt(null)}
                    title={selectedDebt?.counterparty}
                    message={selectedDebt && `المتبقي ${formatAmount(selectedDebt.remaining)}`}
                >
                    {selectedDebt && (
                        <>
                            <SheetAction action={routes.debts.repayments.store(selectedDebt.id)}>انسدّ، سداد كامل</SheetAction>
                            <SheetAction href={routes.debts.repayments.create(selectedDebt.id)}>دفعة جزئية</SheetAction>
                            <SheetAction href={routes.transactions.edit(selectedDebt.id)}>تعديل الدين</SheetAction>
                        </>
                    )}
                </ActionSheet>
            )}
        </>
    );
}
