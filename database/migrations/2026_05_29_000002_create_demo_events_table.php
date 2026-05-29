<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per demo interaction (visit / scan / report) so we can draw
     * daily activity charts and a real "recent events" feed. Loose link to
     * `demo_usages.device_id`; no FK constraint because a visitor can hit a
     * `/demo/scan` endpoint before the row exists (extremely unlikely but
     * cheaper to allow than to enforce).
     */
    public function up(): void
    {
        Schema::create('demo_events', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 64)->index();
            $table->string('type', 16); // 'visit' | 'scan' | 'report'
            $table->string('ip', 45)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('locale', 10)->nullable();
            $table->text('referer')->nullable();
            $table->text('user_agent')->nullable();
            // Events are immutable — only created_at, indexed for time-series queries.
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_events');
    }
};
