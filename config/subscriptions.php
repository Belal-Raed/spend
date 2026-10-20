<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Free Trial
    |--------------------------------------------------------------------------
    |
    | How many days a new user has full access before having to subscribe,
    | and how many days before the end of their access the app warns them.
    |
    */

    'trial_days' => 7,

    'warning_days' => 2,

    /*
    |--------------------------------------------------------------------------
    | Bank Transfer
    |--------------------------------------------------------------------------
    |
    | The account users transfer the subscription fee to, shown on the
    | subscription page. Empty values are left off the page.
    |
    */

    'bank' => [
        'name' => env('SUBSCRIPTION_BANK_NAME'),
        'account_name' => env('SUBSCRIPTION_BANK_ACCOUNT_NAME'),
        'account_number' => env('SUBSCRIPTION_BANK_ACCOUNT_NUMBER'),
        'iban' => env('SUBSCRIPTION_BANK_IBAN'),
    ],

];
