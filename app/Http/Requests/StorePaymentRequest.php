<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
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
     * The receipt is a photo or a PDF of the bank transfer.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', Rule::in($this->purchasablePlanIds())],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
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
            'plan_id.*' => 'اختر باقة من القائمة.',
            'receipt.required' => 'أرفق صورة إشعار التحويل.',
            'receipt.file' => 'أرفق صورة إشعار التحويل.',
            'receipt.mimes' => 'الإشعار يجب أن يكون صورة أو ملف PDF.',
            'receipt.max' => 'حجم الإشعار يجب ألا يزيد عن 5 ميجابايت.',
        ];
    }

    /**
     * A user sends one receipt at a time and waits for the answer.
     *
     * PaymentService enforces the same limit; it is repeated here so the
     * user reads it in Arabic next to the receipt field.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->user()->payments()->pending()->exists()) {
                    $validator->errors()->add('receipt', 'عندك إشعار بانتظار المراجعة. انتظر الرد قبل إرسال إشعار جديد.');
                }
            },
        ];
    }

    public function plan(): Plan
    {
        return Plan::query()->findOrFail($this->integer('plan_id'));
    }

    /**
     * @return list<int>
     */
    private function purchasablePlanIds(): array
    {
        return Plan::query()
            ->offered()
            ->whereNotNull('price')
            ->pluck('id')
            ->all();
    }
}
