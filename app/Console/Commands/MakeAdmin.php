<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PhoneNumber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:make-admin {phone : The phone number the user signs in with} {--revoke : Take the admin panel away instead}')]
#[Description('Let a user open the admin panel, or take it away')]
class MakeAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::query()->where('phone', PhoneNumber::normalize($this->argument('phone')))->first();

        if ($user === null) {
            $this->error('No user signs in with that phone number.');

            return self::FAILURE;
        }

        $user->is_admin = ! $this->option('revoke');
        $user->save();

        $this->info($user->is_admin ? "{$user->name} can now open the admin panel." : "{$user->name} can no longer open the admin panel.");

        return self::SUCCESS;
    }
}
