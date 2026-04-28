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
        Schema::create('waste_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['protein', 'veg', 'dairy', 'prepared']);
            $table->string('item_name', 150);
            $table->decimal('weight_kg', 8, 4);
            $table->enum('reason', ['spoilage', 'overproduction', 'expiry', 'prep_waste', 'other']);
            $table->text('notes')->nullable();
            $table->datetime('logged_at');
            $table->enum('source', ['manual', 'ai_scan'])->default('manual');
            $table->string('photo_path')->nullable();
            $table->json('ai_raw_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'logged_at']);
            $table->index(['category', 'logged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_entries');
    }
};
