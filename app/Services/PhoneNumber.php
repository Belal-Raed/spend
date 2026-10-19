<?php

namespace App\Services;

/**
 * Brings a typed phone number to the one form it is stored and looked up in.
 *
 * "٠٥٩ 912-3456" and "0599123456" are the same number, and so are
 * "00970599123456" and "+970599123456".
 */
final class PhoneNumber
{
    /**
     * A stored phone number: an optional leading "+" then 8 to 15 digits.
     */
    public const PATTERN = '/^\+?[0-9]{8,15}$/';

    /**
     * Arabic-Indic and Persian digits, as typed on an Arabic phone keyboard.
     */
    private const EASTERN_DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /**
     * Turn eastern digits into western ones, drop spaces, dashes, dots and
     * brackets, and write a leading "00" as "+".
     *
     * Anything else is left in place, so a value that is not a phone number
     * still fails the PATTERN check.
     */
    public static function normalize(string $phone): string
    {
        $phone = strtr($phone, self::EASTERN_DIGITS);
        $phone = preg_replace('/[\s\-.()\x{200E}\x{200F}]+/u', '', $phone);

        if (str_starts_with($phone, '00')) {
            return '+'.substr($phone, 2);
        }

        return $phone;
    }
}
