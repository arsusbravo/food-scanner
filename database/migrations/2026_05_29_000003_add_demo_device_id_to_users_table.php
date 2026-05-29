<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a registered user back to the anonymous demo device they used
     * before signing up. Set during registration when the `demo_id` cookie
     * is present. Enables the demo → signup conversion metric.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('demo_device_id', 64)->nullable()->index()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['demo_device_id']);
            $table->dropColumn('demo_device_id');
        });
    }
};
