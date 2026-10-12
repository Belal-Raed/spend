<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->category());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Only the name can change. It may not repeat another category the user
     * sees for the same type.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($this->category())
                    ->where('type', $this->category()->type->value)
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
            'name.required' => 'اكتب اسم التصنيف.',
            'name.max' => 'اسم التصنيف طويل جدًا.',
            'name.unique' => 'هذا التصنيف موجود من قبل.',
        ];
    }

    private function category(): Category
    {
        return $this->route('category');
    }
}
