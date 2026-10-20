import { useState } from 'react';
import { ActionSheet } from '@/components/action-sheet';
import Group from '@/components/group';
import Icon, { type IconName } from '@/components/icon';
import Row from '@/components/row';
import { useInstall } from '@/lib/install';

const steps: Record<'ios' | 'manual', { icon: IconName; text: string }[]> = {
    ios: [
        { icon: 'share', text: 'اضغط زر المشاركة في شريط Safari.' },
        { icon: 'plus-square', text: 'انزل في القائمة واختر «إضافة إلى الشاشة الرئيسية».' },
        { icon: 'check', text: 'اضغط «إضافة»، وافتح مصاريفي من أيقونته.' },
    ],
    manual: [
        { icon: 'ellipsis-vertical', text: 'افتح قائمة المتصفح من النقاط الثلاث.' },
        { icon: 'plus-square', text: 'اختر «تثبيت التطبيق» أو «إضافة إلى الشاشة الرئيسية».' },
        { icon: 'check', text: 'وافق على الإضافة، وافتح مصاريفي من أيقونته.' },
    ],
};

/**
 * Offers to put the app on the phone's home screen, until it is there.
 *
 * On Chrome for Android it opens the browser's install dialog. Elsewhere,
 * Safari above all, no page can install itself, so it shows the steps to
 * follow in the browser's menu instead. Shows nothing on a computer or once
 * the app is installed.
 *
 * - card: a box with the app icon, for the top of the home screen.
 * - row: a settings row in its own group.
 * - link: a line of text, under the sign-in and sign-up forms.
 */
export default function InstallButton({ variant }: { variant: 'card' | 'row' | 'link' }) {
    const { method, install } = useInstall();
    const [areStepsOpen, setAreStepsOpen] = useState(false);

    if (method === null) {
        return null;
    }

    const start = () => (method === 'prompt' ? void install() : setAreStepsOpen(true));

    const trigger = {
        card: (
            <section className="flex items-center gap-3 rounded-xl bg-surface p-4">
                <img src="/icons/icon-192.png" alt="" className="size-12 shrink-0 rounded-[22%]" />

                <div className="flex min-w-0 flex-1 flex-col">
                    <h2 className="font-semibold">ثبّت مصاريفي على جوالك</h2>
                    <p className="text-[13px] text-muted">افتحه من الشاشة الرئيسية مثل أي تطبيق.</p>
                </div>

                <button type="button" onClick={start} className="shrink-0 cursor-pointer rounded-full bg-emerald-600 px-4 py-1.5 text-[15px] font-semibold text-white active:opacity-70">
                    تثبيت
                </button>
            </section>
        ),
        row: (
            <Group>
                <Row onClick={start} icon="phone" tone="bg-tint">
                    تثبيت التطبيق على الجوال
                </Row>
            </Group>
        ),
        link: (
            <button type="button" onClick={start} className="flex cursor-pointer items-center justify-center gap-1.5 text-[15px] text-tint active:opacity-50">
                <Icon name="phone" className="size-5" />
                ثبّت التطبيق على جوالك
            </button>
        ),
    }[variant];

    return (
        <>
            {trigger}

            {method !== 'prompt' && (
                <ActionSheet open={areStepsOpen} onClose={() => setAreStepsOpen(false)} title="تثبيت مصاريفي">
                    <ol className="flex flex-col gap-4 px-5 py-5 text-start text-[15px]">
                        {steps[method].map((step, index) => (
                            <li key={index} className="flex items-center gap-3">
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-fill text-tint">
                                    <Icon name={step.icon} className="size-5" strokeWidth={2} />
                                </span>
                                <span>{step.text}</span>
                            </li>
                        ))}
                    </ol>
                </ActionSheet>
            )}
        </>
    );
}
