import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { ActionSheet, SheetAction } from '@/components/action-sheet';
import Group from '@/components/group';
import Input from '@/components/input';
import Page from '@/components/page';
import Row from '@/components/row';
import { routes } from '@/lib/routes';
import type { Category } from '@/types';

export default function CategoriesEdit({ category, typeLabel }: { category: Category; typeLabel: string }) {
    const [isDeleteSheetOpen, setIsDeleteSheetOpen] = useState(false);

    return (
        <Page title="تعديل التصنيف" modal cancel={routes.categories.index()} submit="category-form">
            <Form id="category-form" action={routes.categories.update(category.id)} method="put">
                <Group title={`تصنيف ${typeLabel}`}>
                    <Input name="name" label="الاسم" defaultValue={category.name} required autoFocus />
                </Group>
            </Form>

            <Group footer="لا يمكن حذف تصنيف مستخدم في حركات.">
                <Row onClick={() => setIsDeleteSheetOpen(true)} destructive chevron={false}>
                    <span className="text-center">حذف التصنيف</span>
                </Row>
            </Group>

            <ActionSheet open={isDeleteSheetOpen} onClose={() => setIsDeleteSheetOpen(false)} title={category.name} message="لا يمكن التراجع عن الحذف.">
                <SheetAction action={routes.categories.destroy(category.id)} method="delete" destructive>
                    حذف التصنيف
                </SheetAction>
            </ActionSheet>
        </Page>
    );
}
