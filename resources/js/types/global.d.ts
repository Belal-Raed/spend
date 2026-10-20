import type { Access, User } from '@/types';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        flashDataType: {
            status?: string;
        };
        sharedPageProps: {
            appName: string;
            auth: {
                user: User | null;
            };
            /** Null for guests. */
            access: Access | null;
            /** Today's date (Y-m-d) in the app's timezone. */
            today: string;
        };
    }
}
