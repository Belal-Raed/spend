<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Store the phone in one form, however the user typed it.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => PhoneNumber::normalize($this->input('phone'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN, Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
            'name.required' => 'اكتب الاسم.',
            'name.max' => 'الاسم طويل جدًا.',
            'phone.required' => 'اكتب رقم الجوال.',
            'phone.regex' => 'رقم الجوال غير صحيح.',
            'phone.unique' => 'رقم الجوال هذا مسجّل من قبل.',
            'password.required' => 'اكتب كلمة المرور.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن :min أحرف.',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
        ];
    }
}
