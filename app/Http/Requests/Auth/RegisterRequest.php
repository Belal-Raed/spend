<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
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
            'email.required' => 'اكتب البريد الإلكتروني.',
            'email.email' => 'البريد الإلكتروني غير صحيح.',
            'email.max' => 'البريد الإلكتروني طويل جدًا.',
            'email.unique' => 'هذا البريد الإلكتروني مسجّل من قبل.',
            'password.required' => 'اكتب كلمة المرور.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن :min أحرف.',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
        ];
    }
}
