<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete()->after('invoice_id');
            $table->integer('qty')->default(1)->after('description');
            $table->decimal('unit_price', 15, 2)->default(0)->after('qty');
            $table->decimal('discount', 15, 2)->default(0)->after('unit_price');
            $table->decimal('tax', 15, 2)->default(0)->after('discount');
            $table->decimal('line_total', 15, 2)->default(0)->after('tax');

            // Keep description for flexibility, but line_total is calculated
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['service_id', 'qty', 'unit_price', 'discount', 'tax', 'line_total']);
            $table->dropIndex('service_id');
        });
    }
};
