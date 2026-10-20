import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Amount from '@/components/amount';
import Button from '@/components/button';
import Group from '@/components/group';
import Icon from '@/components/icon';
import Page from '@/components/page';
import Row from '@/components/row';
import { Segmented } from '@/components/segmented';
import { cx } from '@/lib/cx';
import { formatAmount } from '@/lib/format';
import { routes } from '@/lib/routes';
import { useFieldError } from '@/lib/use-field-error';
import type { Payment, PaymentStatus, Plan } from '@/types';

type Bank = Partial<Record<'name' | 'account_name' | 'account_number' | 'iban', string>>;

const bankLabels: Record<keyof Bank, string> = {
    name: 'البنك',
    account_name: 'اسم الحساب',
    account_number: 'رقم الحساب',
    iban: 'IBAN',
};

const statusColors: Record<PaymentStatus, string> = {
    pending: 'text-warning',
    approved: 'text-positive',
    rejected: 'text-negative',
};

/** A file field as a row: tapping it picks the receipt, and the row then shows the file's name. */
function ReceiptField() {
    const [fileName, setFileName] = useState<string | null>(null);
    const error = useFieldError('receipt');

    return (
        <label className="ios-row flex w-full cursor-pointer items-center gap-3 ps-4 active:bg-pressed">
            <span className="flex size-[30px] shrink-0 items-center justify-center rounded-[7px] bg-tint text-white">
                <Icon name="paper-clip" className="size-[18px]" strokeWidth={2} />
            </span>

            <span className="ios-row-body flex min-h-11 min-w-0 flex-1 flex-col justify-center gap-0.5 py-2.5 pe-4">
                <span className={cx('truncate', fileName ? 'text-label' : 'text-tint')}>{fileName ?? 'إرفاق إشعار التحويل'}</span>
                {error && <span className="text-[13px] text-negative">{error}</span>}
            </span>

            <input
                type="file"
                name="receipt"
                accept="image/jpeg,image/png,image/webp,application/pdf"
                required
                className="sr-only"
                onChange={(event) => setFileName(event.target.files?.[0]?.name ?? null)}
            />
        </label>
    );
}

export default function Subscription({
    plans,
    bank,
    payments,
    hasPendingPayment,
}: {
    plans: Plan[];
    bank: Bank;
    payments: Payment[];
    hasPendingPayment: boolean;
}) {
    const access = usePage().props.access!;
    const purchasablePlans = plans.filter((plan) => plan.is_purchasable);
    const bankRows = (Object.keys(bankLabels) as (keyof Bank)[]).filter((key) => bank[key]);

    return (
        <Page title="الاشتراك" back={routes.settings()} backLabel="الإعدادات" trailing={null}>
            <Group footer={access.status === 'read_only' ? 'حسابك للقراءة فقط: تقدر تشوف حركاتك وتصدّرها، لكن لا تضيف ولا تعدّل حتى تشترك.' : null}>
                <Row detail={<span className={cx(access.status === 'read_only' ? 'text-warning' : 'text-muted')}>{access.status_label}</span>}>الحالة</Row>
                {access.ends_on && (
                    <Row detail={<span dir="ltr" className="text-muted tabular-nums">{access.ends_on}</span>}>
                        {access.status === 'read_only' ? 'انتهى في' : 'ينتهي في'}
                    </Row>
                )}
            </Group>

            <Group title="الباقات" footer={purchasablePlans.length === 0 ? 'الأسعار تُحدَّد قريبًا.' : null}>
                {plans.map((plan) => (
                    <Row key={plan.id} detail={plan.price ? <Amount value={plan.price} className="text-muted" /> : <span className="text-muted">قريبًا</span>}>
                        {plan.name}
                    </Row>
                ))}
            </Group>

            {hasPendingPayment ? (
                <p className="flex items-start gap-2 rounded-xl bg-surface px-4 py-3 text-[15px]">
                    <Icon name="check-circle" className="mt-0.5 size-5 text-tint" />
                    <span>إشعارك بانتظار المراجعة. سنفعّل اشتراكك بعد التأكد من التحويل.</span>
                </p>
            ) : (
                purchasablePlans.length > 0 && (
                    <>
                        {bankRows.length > 0 && (
                            <Group title="حوّل المبلغ إلى">
                                {bankRows.map((key) => (
                                    <Row key={key} detail={<span dir="ltr" className="text-muted select-all">{bank[key]}</span>}>
                                        {bankLabels[key]}
                                    </Row>
                                ))}
                            </Group>
                        )}

                        <Form action={routes.payments.store()} method="post" className="flex flex-col gap-7">
                            {({ processing }) => (
                                <>
                                    <Segmented
                                        name="plan_id"
                                        label="الباقة"
                                        title="الباقة"
                                        options={purchasablePlans.map((plan) => ({ value: String(plan.id), label: `${plan.name} · ${formatAmount(plan.price!)}` }))}
                                        defaultValue={String(purchasablePlans[0].id)}
                                    />

                                    <Group footer="صورة أو ملف PDF، حتى 5 ميجابايت.">
                                        <ReceiptField />
                                    </Group>

                                    <Button className="w-full" disabled={processing}>
                                        إرسال الإشعار
                                    </Button>
                                </>
                            )}
                        </Form>
                    </>
                )
            )}

            {payments.length > 0 && (
                <Group title="الإشعارات المرسلة">
                    {payments.map((payment) => (
                        <Row key={payment.id} detail={<span className={cx('text-[15px]', statusColors[payment.status])}>{payment.status_label}</span>}>
                            <span>
                                {payment.plan} · <Amount value={payment.amount} />
                            </span>
                            <span dir="ltr" className="self-start text-[13px] text-muted tabular-nums">
                                {payment.sent_on}
                            </span>
                            {payment.rejection_reason && <span className="text-[13px] text-negative">{payment.rejection_reason}</span>}
                        </Row>
                    ))}
                </Group>
            )}
        </Page>
    );
}
