# Glossary

Shared vocabulary for the personal expenses app. Use these terms consistently in intents, specs, and code.

| Term | Arabic | Meaning |
|---|---|---|
| User | المستخدم | A person who records their own transactions, from the web app or from the Telegram bot. Signs in to the web app with a phone number and password; a Telegram user may have neither. |
| Transaction | الحركة | Any record of money moving: an expense, an income, or a debt. Has an amount, a date, and an optional note. |
| Expense | المصروف | A transaction for money the user spent. Has a category. |
| Income | القبض | A transaction for money the user received, with where it came from (من وين). Has a category. |
| Debt | الدين | A transaction for money lent or borrowed, with who it is with (على مين) and its direction. Has no category. |
| Debt direction | اتجاه الدين | "Owed by me" (علي): the user borrowed. "Owed to me" (إلي): the user lent. |
| Repayment | دفعة سداد | A payment against a debt, in full or in part. Recorded as income for a debt owed to me, and as an expense for a debt owed by me. |
| Remaining | المتبقي | A debt's amount minus its repayments. |
| Settled | انسدّ | A debt with nothing remaining. It is marked settled on the date of its last repayment. |
| Category | التصنيف | A label used to group expenses or income (e.g. Food, Salary). Defaults are shared by everyone; a user may add their own. |
| Amount | المبلغ | How much money one transaction moved. Always positive. |
| Balance | الرصيد | Income − expenses − money lent + money borrowed. |
| Balance after debts | الرصيد بعد الديون | Balance − what remains of debts owed by me + what remains of debts owed to me. |
| Summary | الملخص | Totals for a period (a day, a week starting Saturday, a month...): total expenses, total income, and expenses per category with their share. |
| Filter | الفلترة | Narrowing the transaction list by period and/or category. |
