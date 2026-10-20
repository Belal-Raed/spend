import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import AccessBanner from '@/components/access-banner';
import Icon, { type IconName } from '@/components/icon';
import Toast from '@/components/toast';
import { cx } from '@/lib/cx';
import { routes } from '@/lib/routes';

/** Each tab, with the paths it is active on. */
const tabs: { href: string; label: string; icon: IconName; activeOn: RegExp }[] = [
    { href: routes.home(), label: 'الرئيسية', icon: 'home', activeOn: /^\/$/ },
    { href: routes.transactions.index(), label: 'الحركات', icon: 'list', activeOn: /^\/transactions/ },
    { href: routes.debts.index(), label: 'الديون', icon: 'exchange', activeOn: /^\/debts/ },
    { href: routes.settings(), label: 'الإعدادات', icon: 'cog', activeOn: /^\/(settings|categories)/ },
];

/**
 * The app shell around every signed-in screen.
 *
 * A main screen has a large title (with an optional subtitle under it), the
 * tab bar, and a + button for a new transaction. A modal screen (a form) has
 * a small title between a cancel link and a save button that submits the
 * form with the id given in `submit`, and no tab bar. `back` adds a back
 * link to a main screen. `trailing` replaces the + button; pass null to drop it.
 */
export default function Page({
    title,
    subtitle,
    modal = false,
    cancel,
    submit,
    submitLabel = 'حفظ',
    back,
    backLabel,
    trailing,
    children,
}: {
    title: string;
    subtitle?: string;
    modal?: boolean;
    cancel?: string;
    submit?: string;
    submitLabel?: string;
    back?: string;
    backLabel?: string;
    trailing?: ReactNode;
    children: ReactNode;
}) {
    const path = usePage().url.split(/[?#]/)[0];
    const scrollArea = useRef<HTMLDivElement>(null);
    const navBar = useRef<HTMLElement>(null);
    const scrollSentinel = useRef<HTMLDivElement>(null);
    const [isScrolled, setIsScrolled] = useState(false);

    // Once the top of the page scrolls under the navigation bar, the bar turns
    // translucent and shows the page title in small, as on iOS.
    useEffect(() => {
        if (!scrollArea.current || !navBar.current || !scrollSentinel.current) {
            return;
        }

        const observer = new IntersectionObserver(([entry]) => setIsScrolled(!entry.isIntersecting), {
            root: scrollArea.current,
            rootMargin: `-${navBar.current.offsetHeight}px 0px 0px 0px`,
        });
        observer.observe(scrollSentinel.current);

        return () => observer.disconnect();
    }, []);

    // The screen itself never scrolls: this area does, with the bars stuck to
    // its top and bottom. Mobile browsers move or hide fixed bars while the
    // page scrolls, but they leave sticky ones inside a scrolling area alone.
    return (
        <div ref={scrollArea} scroll-region="" className="flex h-dvh flex-col overflow-y-auto overscroll-contain">
            <Head title={title} />

            <div className="mx-auto flex w-full max-w-lg flex-1 flex-col">
                <header
                    ref={navBar}
                    data-scrolled={isScrolled || undefined}
                    className={cx(
                        'group sticky top-0 z-20 border-b-[0.5px] border-transparent pt-[env(safe-area-inset-top)] transition-colors duration-200 select-none',
                        'data-scrolled:border-separator data-scrolled:bg-bar data-scrolled:backdrop-blur-xl',
                        modal ? 'bg-bar backdrop-blur-xl' : 'bg-canvas',
                    )}
                >
                    <div className="grid h-11 grid-cols-[1fr_auto_1fr] items-center gap-2 px-4">
                        <div className="flex justify-start">
                            {modal && cancel ? (
                                <Link href={cancel} className="text-tint active:opacity-50">
                                    إلغاء
                                </Link>
                            ) : (
                                back && (
                                    <Link href={back} prefetch className="-ms-2 flex items-center gap-0.5 text-tint active:opacity-50">
                                        <Icon name="chevron-right" className="size-6" strokeWidth={2.25} />
                                        {backLabel}
                                    </Link>
                                )
                            )}
                        </div>

                        <span className={cx('truncate text-center font-semibold', !modal && 'opacity-0 transition-opacity duration-200 group-data-scrolled:opacity-100')}>
                            {title}
                        </span>

                        <div className="flex justify-end">
                            {trailing !== undefined
                                ? trailing
                                : modal
                                  ? submit && (
                                        <button type="submit" form={submit} className="cursor-pointer font-semibold text-tint active:opacity-50">
                                            {submitLabel}
                                        </button>
                                    )
                                  : (
                                        <Link
                                            href={routes.transactions.create()}
                                            prefetch
                                            className="flex size-8 items-center justify-center rounded-full bg-fill text-tint active:opacity-50"
                                            aria-label="حركة جديدة"
                                        >
                                            <Icon name="plus" className="size-5" strokeWidth={2.5} />
                                        </Link>
                                    )}
                        </div>
                    </div>
                </header>

                {!modal && (
                    <div className="flex flex-col gap-0.5 px-4 pt-1 pb-2">
                        <h1 className="text-[34px] leading-tight font-bold">{title}</h1>
                        {subtitle && <p className="text-[15px] text-muted">{subtitle}</p>}
                    </div>
                )}

                <div ref={scrollSentinel} aria-hidden="true" />

                <main
                    className={cx(
                        'flex flex-1 flex-col gap-7 px-4',
                        modal ? 'pt-5 pb-[calc(env(safe-area-inset-bottom)+2.5rem)]' : 'pt-2 pb-8',
                    )}
                >
                    {modal && <h1 className="sr-only">{title}</h1>}
                    {!modal && path !== routes.subscription() && <AccessBanner />}
                    {children}
                </main>
            </div>

            <Toast />

            {!modal && (
                <nav className="sticky bottom-0 z-20 border-t-[0.5px] border-separator bg-bar backdrop-blur-xl select-none" aria-label="التنقل الرئيسي">
                    <div className="mx-auto grid max-w-lg grid-cols-4 pb-[max(env(safe-area-inset-bottom),0.375rem)]">
                        {tabs.map((tab) => {
                            const isActive = tab.activeOn.test(path);

                            return (
                                <Link
                                    key={tab.href}
                                    href={tab.href}
                                    prefetch="mount"
                                    className={cx('flex flex-col items-center gap-0.5 pt-1.5 pb-1 text-[10px] font-medium active:opacity-60', isActive ? 'text-tint' : 'text-muted')}
                                    aria-current={isActive ? 'page' : undefined}
                                >
                                    <Icon name={tab.icon} className="size-7" strokeWidth={isActive ? 2 : 1.5} />
                                    {tab.label}
                                </Link>
                            );
                        })}
                    </div>
                </nav>
            )}
        </div>
    );
}
