import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ActionSheet, SheetAction } from '@/components/action-sheet';
import Group from '@/components/group';
import InstallButton from '@/components/install-button';
import Page from '@/components/page';
import Row from '@/components/row';
import { Segmented } from '@/components/segmented';
import { routes } from '@/lib/routes';
import { savedTheme, saveTheme, type Theme } from '@/lib/theme';
import type { Option } from '@/types';

const themes: Option<Theme>[] = [
    { value: 'auto', label: 'تلقائي' },
    { value: 'light', label: 'فاتح' },
    { value: 'dark', label: 'داكن' },
];

export default function Settings({ customCategoriesCount }: { customCategoriesCount: number }) {
    const { auth, access } = usePage().props;
    const user = auth.user!;
    const [theme, setTheme] = useState(savedTheme);
    const [isLogoutSheetOpen, setIsLogoutSheetOpen] = useState(false);

    return (
        <Page title="الإعدادات" trailing={null}>
            <section className="flex items-center gap-4 rounded-xl bg-surface p-4">
                <span
                    className="flex size-14 shrink-0 items-center justify-center rounded-full bg-linear-to-bl from-emerald-400 to-emerald-700 text-2xl font-semibold text-white"
                    aria-hidden="true"
                >
                    {Array.from(user.name)[0]}
                </span>

                <div className="flex min-w-0 flex-col">
                    <span className="truncate text-[20px] font-semibold">{user.name}</span>

                    {user.phone && (
                        <span dir="ltr" className="self-start text-[15px] text-muted tabular-nums">
                            {user.phone}
                        </span>
                    )}
                </div>
            </section>

            <InstallButton variant="row" />

            {/* The choice is kept on this device only. */}
            <Segmented
                name="theme"
                label="المظهر"
                title="المظهر"
                options={themes}
                value={theme}
                onChange={(choice) => {
                    setTheme(choice);
                    saveTheme(choice);
                }}
            />

            <Group>
                <Row href={routes.subscription()} icon="credit-card" tone="bg-tint" detail={<span className="text-muted">{access?.status_label}</span>}>
                    الاشتراك
                </Row>
                <Row
                    href={routes.categories.index()}
                    icon="tag"
                    tone="bg-warning"
                    detail={customCategoriesCount > 0 && <span className="text-muted tabular-nums">{customCategoriesCount}</span>}
                >
                    التصنيفات
                </Row>
            </Group>

            <Group footer="ملف CSV فيه كل حركاتك، يفتح في Excel.">
                <Row href={routes.export()} download icon="download" tone="bg-positive">
                    تصدير بياناتي
                </Row>
            </Group>

            <Group>
                <Row onClick={() => setIsLogoutSheetOpen(true)} destructive chevron={false}>
                    <span className="text-center">تسجيل الخروج</span>
                </Row>
            </Group>

            <ActionSheet open={isLogoutSheetOpen} onClose={() => setIsLogoutSheetOpen(false)} message="تريد تسجيل الخروج من مصاريفي؟">
                <SheetAction action={routes.logout()} destructive>
                    تسجيل الخروج
                </SheetAction>
            </ActionSheet>
        </Page>
    );
}
