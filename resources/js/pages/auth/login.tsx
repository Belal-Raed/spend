import { Form, Link } from '@inertiajs/react';
import AuthCard from '@/components/auth-card';
import Button from '@/components/button';
import Group from '@/components/group';
import Input from '@/components/input';
import Switch from '@/components/switch';
import { routes } from '@/lib/routes';

export default function Login() {
    return (
        <AuthCard title="تسجيل الدخول">
            <Form action={routes.login()} method="post" resetOnError={['password']} className="flex flex-col gap-6">
                {({ processing }) => (
                    <>
                        <Group>
                            <Input name="phone" type="tel" label="رقم الجوال" dir="ltr" className="text-end" autoComplete="username" placeholder="0599123456" required autoFocus />
                            <Input name="password" type="password" label="كلمة المرور" dir="ltr" className="text-end" autoComplete="current-password" required />

                            <label className="ios-row flex w-full cursor-pointer items-center ps-4">
                                <span className="ios-row-body flex min-h-11 flex-1 items-center justify-between gap-3 py-1.5 pe-4">
                                    تذكرني
                                    <Switch name="remember" />
                                </span>
                            </label>
                        </Group>

                        <Button className="w-full" disabled={processing}>
                            دخول
                        </Button>
                    </>
                )}
            </Form>

            <p className="text-center text-[15px] text-muted">
                ليس لديك حساب؟{' '}
                <Link href={routes.register()} className="font-semibold text-tint active:opacity-50">
                    أنشئ حسابًا
                </Link>
            </p>
        </AuthCard>
    );
}
