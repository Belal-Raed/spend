import { Link, usePage } from '@inertiajs/react';
import Amount from '@/components/amount';
import DebtList from '@/components/debt-list';
import Group from '@/components/group';
import Page from '@/components/page';
import Row from '@/components/row';
import TransactionItem from '@/components/transaction-item';
import { routes } from '@/lib/routes';
import type { Balance, Debt, Transaction } from '@/types';

export default function Home({
    balance,
    monthExpensesTotal,
    debtsOwedByMe,
    debtsOwedToMe,
    recentTransactions,
}: {
    balance: Balance;
    monthExpensesTotal: number;
    debtsOwedByMe: Debt[];
    debtsOwedToMe: Debt[];
    recentTransactions: Transaction[];
}) {
    const { auth } = usePage().props;

    return (
        <Page title="الرئيسية" subtitle={`أهلًا، ${auth.user?.name}`}>
            <section className="flex flex-col gap-5 rounded-2xl bg-linear-to-bl from-emerald-500 to-emerald-800 p-5 text-white shadow-lg shadow-emerald-900/20">
                <div className="flex flex-col gap-1">
                    <h2 className="text-[15px] font-medium text-white/75">الرصيد</h2>
                    <Amount value={balance.cash} className="text-[40px] leading-tight font-bold" />
                </div>

                <dl className="grid grid-cols-2 gap-3">
                    <div className="flex flex-col gap-0.5 rounded-xl bg-white/12 px-3 py-2.5">
                        <dt className="text-[13px] text-white/75">ناقص الديون اللي عليّ</dt>
                        <dd>
                            <Amount value={-balance.owed_by_me} className="text-[17px] font-semibold" />
                        </dd>
                    </div>
                    <div className="flex flex-col gap-0.5 rounded-xl bg-white/12 px-3 py-2.5">
                        <dt className="text-[13px] text-white/75">زائد الديون اللي إلي</dt>
                        <dd>
                            <Amount value={balance.owed_to_me} className="text-[17px] font-semibold" />
                        </dd>
                    </div>
                </dl>
            </section>

            <dl className="grid grid-cols-2 gap-3">
                <div className="flex flex-col gap-1 rounded-xl bg-surface p-4">
                    <dt className="text-[13px] text-muted">الرصيد بعد الديون</dt>
                    <dd>
                        <Amount value={balance.after_debts} className="text-[22px] font-bold" />
                    </dd>
                </div>
                <div className="flex flex-col gap-1 rounded-xl bg-surface p-4">
                    <dt className="text-[13px] text-muted">مصاريف هذا الشهر</dt>
                    <dd>
                        <Amount value={monthExpensesTotal} className="text-[22px] font-bold text-negative" />
                    </dd>
                </div>
            </dl>

            <DebtList title="ديون عليّ" debts={debtsOwedByMe} empty="ما عليك ديون مفتوحة." />
            <DebtList title="ديون إلي" debts={debtsOwedToMe} empty="ما إلك ديون مفتوحة." />

            <Group
                title="آخر الحركات"
                action={
                    <Link href={routes.transactions.index()} className="text-[15px] text-tint active:opacity-50">
                        عرض الكل
                    </Link>
                }
            >
                {recentTransactions.length > 0 ? (
                    recentTransactions.map((transaction) => <TransactionItem key={transaction.id} transaction={transaction} showDate />)
                ) : (
                    <Row>
                        <span className="text-muted">لا توجد حركات بعد.</span>
                    </Row>
                )}
            </Group>
        </Page>
    );
}
