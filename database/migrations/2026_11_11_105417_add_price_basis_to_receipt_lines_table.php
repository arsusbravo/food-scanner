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
        Schema::table('receipt_lines', function (Blueprint $table) {
            $table->string('base_unit', 3)->nullable()->after('category');
            $table->decimal('base_price', 12, 4)->nullable()->after('base_unit');
            $table->decimal('base_quantity', 12, 3)->nullable()->after('base_price');
            $table->decimal('pack_size', 10, 3)->nullable()->after('base_quantity');
            $table->string('pack_unit', 2)->nullable()->after('pack_size');

            $table->index(['normalized_item', 'base_unit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_lines', function (Blueprint $table) {
            $table->dropIndex(['normalized_item', 'base_unit']);
            $table->dropColumn(['base_unit', 'base_price', 'base_quantity', 'pack_size', 'pack_unit']);
        });
    }
};
