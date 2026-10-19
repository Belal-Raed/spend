<?php

namespace Tests\Unit\Services;

use App\Services\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function typedNumbers(): array
    {
        return [
            'already clean' => ['0599123456', '0599123456'],
            'spaces and dashes' => [' 059 912-3456 ', '0599123456'],
            'dots and brackets' => ['(059) 912.3456', '0599123456'],
            'arabic-indic digits' => ['٠٥٩٩١٢٣٤٥٦', '0599123456'],
            'persian digits' => ['۰۵۹۹۱۲۳۴۵۶', '0599123456'],
            'international with plus' => ['+970 599 123 456', '+970599123456'],
            'international with 00' => ['00970599123456', '+970599123456'],
            'direction marks pasted with the number' => ["\u{200E}+970599123456\u{200E}", '+970599123456'],
        ];
    }

    #[DataProvider('typedNumbers')]
    public function test_normalizes_typed_number(string $typed, string $stored): void
    {
        $this->assertSame($stored, PhoneNumber::normalize($typed));
    }

    public function test_normalized_numbers_match_the_pattern(): void
    {
        $this->assertMatchesRegularExpression(PhoneNumber::PATTERN, PhoneNumber::normalize('٠٥٩ ٩١٢ ٣٤٥٦'));
        $this->assertMatchesRegularExpression(PhoneNumber::PATTERN, PhoneNumber::normalize('00970599123456'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function notPhoneNumbers(): array
    {
        return [
            'email' => ['abu.mohammad@example.com'],
            'letters' => ['059abc3456'],
            'too short' => ['0599123'],
            'too long' => ['+9705991234567890'],
            'plus in the middle' => ['0599+123456'],
        ];
    }

    #[DataProvider('notPhoneNumbers')]
    public function test_rejects_what_is_not_a_phone_number(string $typed): void
    {
        $this->assertDoesNotMatchRegularExpression(PhoneNumber::PATTERN, PhoneNumber::normalize($typed));
    }
}
