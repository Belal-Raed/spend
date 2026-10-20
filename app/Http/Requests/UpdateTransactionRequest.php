<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->transaction());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The type and debt direction cannot change, and only the fields that
     * belong to the transaction's type are accepted.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $transaction = $this->transaction();

        $rules = [
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'occurred_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];

        if ($transaction->isGroupedByCategory()) {
            $rules['category_id'] = ['required', 'integer', Rule::in($this->selectableCategoryIds())];
        }

        if ($transaction->isDebt()) {
            $rules['counterparty'] = ['required', 'string', 'max:255'];
        } elseif ($transaction->type === TransactionType::Income && ! $transaction->isRepayment()) {
            $rules['counterparty'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'اكتب المبلغ.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقمًا.',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'amount.max' => 'المبلغ كبير جدًا.',
            'occurred_on.*' => 'اختر تاريخًا صحيحًا.',
            'note.max' => 'الملاحظة طويلة جدًا.',
            'category_id.*' => 'اختر تصنيفًا من القائمة.',
            'counterparty.required' => 'اكتب اسم الشخص.',
            'counterparty.max' => 'الاسم طويل جدًا.',
        ];
    }

    /**
     * Keep a debt and its payments consistent: a debt cannot drop below what
     * was already paid, and a payment cannot exceed what is left on its debt.
     *
     * TransactionService enforces the same limits; they are repeated here so
     * the user reads them in Arabic next to the amount field.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('amount')) {
                    return;
                }

                $transaction = $this->transaction();
                $newAmountInCents = Money::toCents($this->input('amount'));

                if ($transaction->isDebt() && $newAmountInCents < $transaction->repaidInCents()) {
                    $repaid = number_format(Money::fromCents($transaction->repaidInCents()), 2);
                    $validator->errors()->add('amount', "المبلغ لا يمكن أن يقل عن المسدَّد من الدين ({$repaid}).");
                }

                if ($transaction->isRepayment()) {
                    $leftInCents = $transaction->debt->remainingInCents() + $transaction->amountInCents();

                    if ($newAmountInCents > $leftInCents) {
                        $left = number_format(Money::fromCents($leftInCents), 2);
                        $validator->errors()->add('amount', "الدفعة لا يمكن أن تزيد عن المتبقي من الدين ({$left}).");
                    }
                }
            },
        ];
    }

    private function transaction(): Transaction
    {
        return $this->route('transaction');
    }

    /**
     * The default categories and the user's own, of the transaction's type.
     *
     * @return list<int>
     */
    private function selectableCategoryIds(): array
    {
        return Category::query()
            ->ofType($this->transaction()->type)
            ->pluck('id')
            ->all();
    }
}
