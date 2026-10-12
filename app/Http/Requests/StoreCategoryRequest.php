<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
     * A name may not repeat one the user already sees for the same type,
     * whether it is a shared default or one of their own.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([TransactionType::Expense->value, TransactionType::Income->value])],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where('type', $this->input('type'))
                    ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', $this->user()->id)),
            ],
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
            'type.*' => 'اختر نوع التصنيف.',
            'name.required' => 'اكتب اسم التصنيف.',
            'name.max' => 'اسم التصنيف طويل جدًا.',
            'name.unique' => 'هذا التصنيف موجود من قبل.',
        ];
    }
}
