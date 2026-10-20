import { usePage } from '@inertiajs/react';
import AmountField from '@/components/amount-field';
import Group from '@/components/group';
import Input from '@/components/input';
import Select from '@/components/select';
import type { Category, Transaction } from '@/types';

/**
 * The fields shared by the create and edit forms.
 *
 * `transaction` is null on the create form. `categories` is empty when the
 * transaction carries no category, and `counterpartyLabel` is null when it
 * carries no counterparty.
 */
export default function TransactionFields({
    transaction,
    categories,
    counterpartyLabel,
    counterpartyPlaceholder,
}: {
    transaction: Transaction | null;
    categories: Category[];
    counterpartyLabel: string | null;
    counterpartyPlaceholder: string;
}) {
    const { today } = usePage().props;

    return (
        <>
            <AmountField defaultValue={transaction?.amount} autoFocus={transaction === null} />

            <Group>
                {categories.length > 0 && (
                    <Select name="category_id" label="التصنيف" defaultValue={transaction?.category_id ?? undefined} required>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </Select>
                )}

                {counterpartyLabel !== null && (
                    <Input name="counterparty" label={counterpartyLabel} defaultValue={transaction?.counterparty ?? undefined} placeholder={counterpartyPlaceholder} />
                )}

                <Input name="occurred_on" type="date" label="التاريخ" defaultValue={transaction?.occurred_on ?? today} dir="ltr" required />

                <Input name="note" label="ملاحظة" defaultValue={transaction?.note ?? undefined} placeholder="اختياري" />
            </Group>
        </>
    );
}
