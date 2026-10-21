<?php

namespace App\Filament\Pages\Auth;

use App\Services\PhoneNumber;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * The admin panel's sign-in, by phone number like the rest of the app, since users have no email.
 */
class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label('رقم الجوال')
            ->tel()
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['dir' => 'ltr']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'phone' => PhoneNumber::normalize($data['phone']),
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.phone' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
