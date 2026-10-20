import { Form, usePage } from '@inertiajs/react';
import Alert from '@/components/alert';
import Button from '@/components/button';
import Group from '@/components/group';
import Input from '@/components/input';
import Page from '@/components/page';
import Row from '@/components/row';
import { Segmented } from '@/components/segmented';
import { routes } from '@/lib/routes';
import type { Category, TransactionType } from '@/types';

const groups: { title: string; type: TransactionType }[] = [
    { title: 'تصنيفات المصاريف', type: 'expense' },
    { title: 'تصنيفات القبض', type: 'income' },
];

export default function CategoriesIndex({ categories }: { categories: Partial<Record<TransactionType, Category[]>> }) {
    const { errors } = usePage().props;

    return (
        <Page title="التصنيفات" back={routes.settings()} backLabel="الإعدادات" trailing={null}>
            {errors.category && <Alert>{errors.category}</Alert>}

            {groups.map(({ title, type }, index) => (
                <Group
                    key={type}
                    title={title}
                    footer={index === groups.length - 1 ? 'التصنيفات الافتراضية مشتركة ولا تُعدَّل. اضغط على تصنيفك لتعديله أو حذفه.' : null}
                >
                    {(categories[type] ?? []).map((category) =>
                        category.is_default ? (
                            <Row key={category.id} detail={<span className="text-[15px] text-muted">افتراضي</span>}>
                                {category.name}
                            </Row>
                        ) : (
                            <Row key={category.id} href={routes.categories.edit(category.id)}>
                                {category.name}
                            </Row>
                        ),
                    )}
                </Group>
            ))}

            <Form action={routes.categories.store()} method="post" resetOnSuccess={['name']} className="flex flex-col gap-3">
                {({ processing }) => (
                    <>
                        <Group title="تصنيف جديد">
                            <Input name="name" label="الاسم" placeholder="مثلًا: أقساط" required />
                        </Group>

                        <Segmented
                            name="type"
                            label="نوع التصنيف"
                            options={[
                                { value: 'expense', label: 'مصروف' },
                                { value: 'income', label: 'قبض' },
                            ]}
                            defaultValue="expense"
                        />

                        <Button className="w-full" disabled={processing}>
                            إضافة التصنيف
                        </Button>
                    </>
                )}
            </Form>
        </Page>
    );
}
