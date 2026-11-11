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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_name', 150)->nullable();
            $table->string('supplier_vat_id', 50)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->decimal('net_total', 10, 2)->nullable();
            $table->decimal('vat_total', 10, 2)->nullable();
            $table->decimal('gross_total', 10, 2)->nullable();
            $table->string('confidence', 10)->default('medium');
            $table->string('status', 10)->default('draft');
            $table->text('notes')->nullable();
            $table->text('ai_raw_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'invoice_date']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
