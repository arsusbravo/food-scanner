<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan')->default('free')->after('is_active');
            $table->timestamp('plan_expires_at')->nullable()->after('plan');
            $table->unsignedInteger('ai_scan_limit')->nullable()->after('plan_expires_at');
            $table->unsignedInteger('export_limit')->nullable()->after('ai_scan_limit');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan', 'plan_expires_at', 'ai_scan_limit', 'export_limit']);
        });
    }
};
