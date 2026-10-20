<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A user has full access until the later of the two dates and is
     * read-only after it. Users who signed up before subscriptions existed
     * get a 7-day trial starting now.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('password');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
        });

        DB::table('users')->update(['trial_ends_at' => now()->addDays(7)]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'subscription_ends_at']);
        });
    }
};
