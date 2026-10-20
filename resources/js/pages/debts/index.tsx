import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Alert from '@/components/alert';
import DebtList from '@/components/debt-list';
import Page from '@/components/page';
import { cx } from '@/lib/cx';
import { routes } from '@/lib/routes';
import type { Debt } from '@/types';

type Tab = 'owed-by-me' | 'owed-to-me';

export default function DebtsIndex({ debtsOwedByMe, debtsOwedToMe }: { debtsOwedByMe: Debt[]; debtsOwedToMe: Debt[] }) {
    const { props, url } = usePage();
    const { errors } = props;

    // The open tab is kept in the address (#owed-to-me), so it comes back after recording a payment or going back.
    const [openTab, setOpenTab] = useState<Tab>(() => (url.endsWith('#owed-to-me') ? 'owed-to-me' : 'owed-by-me'));

    const tabs: { id: Tab; label: string; debts: Debt[]; empty: string }[] = [
        { id: 'owed-by-me', label: 'ديون عليّ', debts: debtsOwedByMe, empty: 'ما عليك ديون مفتوحة.' },
        { id: 'owed-to-me', label: 'ديون إلي', debts: debtsOwedToMe, empty: 'ما إلك ديون مفتوحة.' },
    ];

    const openTabDetails = tabs.find((tab) => tab.id === openTab)!;

    const switchTo = (tab: Tab) => {
        setOpenTab(tab);
        router.replace({ url: routes.debts.index() + (tab === 'owed-to-me' ? '#owed-to-me' : ''), preserveState: true, preserveScroll: true });
    };

    return (
        <Page title="الديون">
            {errors.amount && <Alert>{errors.amount}</Alert>}

            <div className="grid grid-cols-2 gap-0.5 rounded-[9px] bg-fill p-0.5 select-none" role="radiogroup" aria-label="اتجاه الدين">
                {tabs.map((tab) => (
                    <label
                        key={tab.id}
                        className={cx(
                            'flex min-h-8 cursor-pointer items-center justify-center gap-1.5 rounded-[7px] text-[13px] font-semibold transition',
                            'has-checked:bg-segment has-checked:shadow-sm has-checked:shadow-black/10 has-focus-visible:ring-2 has-focus-visible:ring-tint',
                        )}
                    >
                        <input type="radio" name="debts-tab" className="sr-only" checked={openTab === tab.id} onChange={() => switchTo(tab.id)} />
                        {tab.label}
                        <span className="font-normal text-muted tabular-nums">{tab.debts.length}</span>
                    </label>
                ))}
            </div>

            <DebtList key={openTab} debts={openTabDetails.debts} empty={openTabDetails.empty} actions />

            <p className="px-4 text-center text-[13px] text-muted">اضغط على الدين لتسديده كاملًا أو بدفعة جزئية.</p>
        </Page>
    );
}
