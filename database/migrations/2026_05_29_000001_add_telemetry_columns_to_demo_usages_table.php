<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * First-touch acquisition fields, captured the first time we issue the
     * `demo_id` cookie for a device. All nullable — `referer`/`country` are
     * unknown for direct hits / non-Cloudflare environments respectively.
     */
    public function up(): void
    {
        Schema::table('demo_usages', function (Blueprint $table) {
            $table->char('country', 2)->nullable()->after('ip');
            $table->string('locale', 10)->nullable()->after('country');
            $table->text('referer')->nullable()->after('locale');
            $table->text('user_agent')->nullable()->after('referer');
        });
    }

    public function down(): void
    {
        Schema::table('demo_usages', function (Blueprint $table) {
            $table->dropColumn(['country', 'locale', 'referer', 'user_agent']);
        });
    }
};
