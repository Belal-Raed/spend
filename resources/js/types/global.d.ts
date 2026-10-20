import type { User } from '@/types';

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
            /** Today's date (Y-m-d) in the app's timezone. */
            today: string;
        };
    }
}
