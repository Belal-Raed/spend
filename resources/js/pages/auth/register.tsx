import { Form, Link } from '@inertiajs/react';
import AuthCard from '@/components/auth-card';
import Button from '@/components/button';
import Group from '@/components/group';
import Input from '@/components/input';
import { routes } from '@/lib/routes';

export default function Register() {
    return (
        <AuthCard title="إنشاء حساب">
            <Form action={routes.register()} method="post" resetOnError={['password', 'password_confirmation']} className="flex flex-col gap-6">
                {({ processing }) => (
                    <>
                        <Group>
                            <Input name="name" label="الاسم" autoComplete="name" placeholder="مثلًا: أبو محمد" required autoFocus />
                            <Input name="phone" type="tel" label="رقم الجوال" dir="ltr" className="text-end" autoComplete="username" placeholder="0599123456" required />
                        </Group>

                        <Group footer="8 أحرف على الأقل.">
                            <Input name="password" type="password" label="كلمة المرور" dir="ltr" className="text-end" autoComplete="new-password" required />
                            <Input
                                name="password_confirmation"
                                type="password"
                                label="التأكيد"
                                dir="ltr"
                                className="text-end"
                                autoComplete="new-password"
                                placeholder="أعد كتابتها"
                                required
                            />
                        </Group>

                        <Button className="w-full" disabled={processing}>
                            إنشاء الحساب
                        </Button>
                    </>
                )}
            </Form>

            <p className="text-center text-[15px] text-muted">
                لديك حساب؟{' '}
                <Link href={routes.login()} className="font-semibold text-tint active:opacity-50">
                    سجّل الدخول
                </Link>
            </p>
        </AuthCard>
    );
}
