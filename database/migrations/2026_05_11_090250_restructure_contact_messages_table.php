<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing rows — dev data only, conversations table replaces grouping
        DB::table('contact_messages')->truncate();

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'subject']);
            $table->foreignId('conversation_id')
                ->after('id')
                ->constrained('contact_conversations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('subject', 50)->after('user_id');
        });
    }
};
