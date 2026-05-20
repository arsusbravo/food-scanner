<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-device usage meter for the public, no-signup demo. There is no user
     * here; the row is keyed by a signed `demo_id` cookie. Per-IP/day ceilings
     * are enforced separately via the RateLimiter (no column needed).
     */
    public function up(): void
    {
        Schema::create('demo_usages', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 64)->unique();
            $table->string('ip', 45)->nullable();
            $table->unsignedInteger('scans')->default(0);
            $table->unsignedInteger('reports')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_usages');
    }
};
