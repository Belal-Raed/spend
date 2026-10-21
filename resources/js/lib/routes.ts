/**
 * The app's URLs, matching routes/web.php.
 */
export const routes = {
    home: () => '/',
    login: () => '/login',
    register: () => '/register',
    logout: () => '/logout',
    settings: () => '/settings',
    export: () => '/export',
    subscription: () => '/subscription',

    payments: {
        store: () => '/payments',
    },

    telegram: {
        link: () => '/telegram/link',
        unlink: () => '/telegram/link',
    },

    transactions: {
        index: () => '/transactions',
        create: (type?: string) => (type ? `/transactions/create?type=${type}` : '/transactions/create'),
        store: () => '/transactions',
        edit: (id: number) => `/transactions/${id}/edit`,
        update: (id: number) => `/transactions/${id}`,
        destroy: (id: number) => `/transactions/${id}`,
    },

    debts: {
        index: () => '/debts',
        repayments: {
            create: (debtId: number) => `/debts/${debtId}/repayments/create`,
            store: (debtId: number) => `/debts/${debtId}/repayments`,
        },
    },

    categories: {
        index: () => '/categories',
        store: () => '/categories',
        edit: (id: number) => `/categories/${id}/edit`,
        update: (id: number) => `/categories/${id}`,
        destroy: (id: number) => `/categories/${id}`,
    },
};
