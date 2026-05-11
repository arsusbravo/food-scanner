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
        Schema::create('contact_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 50);
            $table->boolean('unread_by_user')->default(false);
            $table->boolean('unread_by_admin')->default(true);
            $table->timestamp('last_message_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'last_message_at']);
            $table->index('unread_by_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_conversations');
    }
};
