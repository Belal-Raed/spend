<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Draws a user's avatar as the first letter of their name, inside the app.
 *
 * Filament's default asks ui-avatars.com for the picture, which sends every
 * name it shows to that site.
 */
class InitialAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initial = htmlspecialchars(mb_substr(Filament::getNameForDefaultAvatar($record), 0, 1), ENT_XML1);

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                <rect width="64" height="64" fill="#059669"/>
                <text x="32" y="32" dy=".35em" text-anchor="middle" font-family="system-ui, sans-serif" font-size="30" fill="#ffffff">{$initial}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
