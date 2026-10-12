<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot of the "Invoiced To" block on each invoice, so printed
     * invoices keep the billing details even if the client changes later.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('billing_company')->nullable()->after('client_id');
            $table->string('billing_name')->nullable()->after('billing_company');
            $table->text('billing_address')->nullable()->after('billing_name');
            $table->string('billing_phone')->nullable()->after('billing_address');
            $table->string('billing_email')->nullable()->after('billing_phone');
            $table->string('billing_country', 100)->nullable()->after('billing_email');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['billing_company', 'billing_name', 'billing_address', 'billing_phone', 'billing_email', 'billing_country']);
        });
    }
};
