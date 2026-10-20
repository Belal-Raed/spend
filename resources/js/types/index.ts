export type User = {
    id: number;
    name: string;
    phone: string | null;
};

export type TransactionType = 'expense' | 'income' | 'debt';

export type DebtDirection = 'owed_by_me' | 'owed_to_me';

export type Option<Value extends string = string> = {
    value: Value;
    label: string;
};

/** An expense, an income or a debt, as TransactionResource sends it. */
export type Transaction = {
    id: number;
    type: TransactionType;
    type_label: string;
    amount: string;
    category_id: number | null;
    /** Left out when the server did not load the category. */
    category?: string | null;
    counterparty: string | null;
    debt_direction: DebtDirection | null;
    debt_direction_label: string | null;
    note: string | null;
    occurred_on: string;
    is_debt: boolean;
    is_repayment: boolean;
    is_settled: boolean;
    adds_to_balance: boolean;
};

export type Category = {
    id: number;
    name: string;
    type: TransactionType;
    is_default: boolean;
};

/** An open debt, as ReportService::openDebts() returns it. */
export type Debt = {
    id: number;
    counterparty: string;
    direction: DebtDirection;
    amount: number;
    repaid: number;
    remaining: number;
    occurred_on: string;
    note: string | null;
};

export type Balance = {
    cash: number;
    owed_by_me: number;
    owed_to_me: number;
    after_debts: number;
};
