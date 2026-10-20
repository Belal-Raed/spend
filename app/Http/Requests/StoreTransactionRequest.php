<?php

namespace App\Http\Requests;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Fields that do not belong to the chosen type are dropped, so a debt
     * never carries a category and an expense never carries a debt direction.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $debt = TransactionType::Debt->value;
        $expense = TransactionType::Expense->value;

        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'occurred_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'category_id' => ["exclude_if:type,{$debt}", 'required', 'integer', Rule::in($this->selectableCategoryIds())],
            'counterparty' => ["exclude_if:type,{$expense}", 'nullable', "required_if:type,{$debt}", 'string', 'max:255'],
            'debt_direction' => ["exclude_unless:type,{$debt}", 'required', Rule::enum(DebtDirection::class)],
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
            'type.*' => 'اختر نوع الحركة.',
            'amount.required' => 'اكتب المبلغ.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقمًا.',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'amount.max' => 'المبلغ كبير جدًا.',
            'occurred_on.*' => 'اختر تاريخًا صحيحًا.',
            'note.max' => 'الملاحظة طويلة جدًا.',
            'category_id.*' => 'اختر تصنيفًا من القائمة.',
            'counterparty.required_if' => 'اكتب اسم الشخص.',
            'counterparty.max' => 'الاسم طويل جدًا.',
            'debt_direction.*' => 'اختر اتجاه الدين.',
        ];
    }

    /**
     * The default categories and the user's own, of the chosen transaction type.
     *
     * @return list<int>
     */
    private function selectableCategoryIds(): array
    {
        $type = TransactionType::tryFrom((string) $this->input('type'));

        if ($type === null || ! $type->hasCategory()) {
            return [];
        }

        return Category::query()->ofType($type)->pluck('id')->all();
    }
}
