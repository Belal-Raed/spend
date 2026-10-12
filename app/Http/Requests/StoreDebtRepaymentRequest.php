<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Services\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDebtRepaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('repay', $this->debt());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'occurred_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.numeric' => 'المبلغ يجب أن يكون رقمًا.',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'amount.max' => 'المبلغ كبير جدًا.',
            'occurred_on.*' => 'اختر تاريخًا صحيحًا.',
            'note.max' => 'الملاحظة طويلة جدًا.',
        ];
    }

    /**
     * A settled debt takes no more payments, and a payment cannot exceed what is left.
     *
     * TransactionService enforces the same limits; they are repeated here so
     * the user reads them in Arabic.
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

                $remainingInCents = $this->debt()->remainingInCents();

                if ($this->debt()->isSettled() || $remainingInCents <= 0) {
                    $validator->errors()->add('amount', 'هذا الدين انسدّ من قبل.');

                    return;
                }

                if ($this->filled('amount') && Money::toCents($this->input('amount')) > $remainingInCents) {
                    $remaining = number_format(Money::fromCents($remainingInCents), 2);
                    $validator->errors()->add('amount', "الدفعة لا يمكن أن تزيد عن المتبقي من الدين ({$remaining}).");
                }
            },
        ];
    }

    private function debt(): Transaction
    {
        return $this->route('debt');
    }
}
