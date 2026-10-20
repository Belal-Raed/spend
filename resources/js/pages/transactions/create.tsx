import { Form, usePage } from '@inertiajs/react';
import Alert from '@/components/alert';
import Page from '@/components/page';
import { Segmented, SegmentedLinks } from '@/components/segmented';
import TransactionFields from '@/components/transaction-fields';
import { routes } from '@/lib/routes';
import type { Category, Option, TransactionType } from '@/types';

export default function TransactionsCreate({ type, types, categories }: { type: TransactionType; types: Option<TransactionType>[]; categories: Category[] }) {
    const { errors } = usePage().props;

    return (
        <Page title="حركة جديدة" modal cancel={routes.transactions.index()} submit="transaction-form">
            <SegmentedLinks
                label="نوع الحركة"
                links={types.map((option) => ({ label: option.label, href: routes.transactions.create(option.value), isActive: option.value === type }))}
            />

            <Form id="transaction-form" action={routes.transactions.store()} method="post" className="flex flex-col gap-7">
                <input type="hidden" name="type" value={type} />

                {errors.type && <Alert>{errors.type}</Alert>}

                {type === 'debt' && (
                    <Segmented
                        name="debt_direction"
                        label="اتجاه الدين"
                        title="اتجاه الدين"
                        options={[
                            { value: 'owed_by_me', label: 'عليّ (استدنت)' },
                            { value: 'owed_to_me', label: 'إلي (داينت)' },
                        ]}
                    />
                )}

                <TransactionFields
                    transaction={null}
                    categories={categories}
                    counterpartyLabel={{ debt: 'مع مين', income: 'من وين', expense: null }[type]}
                    counterpartyPlaceholder={type === 'debt' ? 'اسم الشخص' : 'اختياري'}
                />
            </Form>
        </Page>
    );
}
