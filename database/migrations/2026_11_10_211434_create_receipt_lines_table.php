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
        Schema::create('receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->string('description', 255);
            $table->string('normalized_item', 255)->nullable()->index();
            $table->decimal('quantity', 10, 3)->nullable();
            $table->string('unit', 10)->nullable();
            $table->decimal('unit_price', 10, 4)->nullable();
            $table->decimal('line_total', 10, 2)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->string('category', 20)->default('other');
            $table->decimal('price_per_kg', 10, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_lines');
    }
};
