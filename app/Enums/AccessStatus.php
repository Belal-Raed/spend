<?php

namespace App\Enums;

/**
 * What a user may do with their account right now.
 */
enum AccessStatus: string
{
    case Trial = 'trial';
    case Subscribed = 'subscribed';
    case ReadOnly = 'read_only';

    /**
     * The name shown to the user.
     */
    public function label(): string
    {
        return match ($this) {
            self::Trial => 'فترة تجربة',
            self::Subscribed => 'مشترك',
            self::ReadOnly => 'للقراءة فقط',
        };
    }
}
