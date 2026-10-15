<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_payments', function (Blueprint $table) {
            $table->date('payment_date')->nullable()->after('transaction_date');
			$table->index('payment_date'); 
        });

        DB::table('account_payments')
            ->whereNull('payment_date')
            ->update(['payment_date' => DB::raw('transaction_date')]);
    }

    public function down(): void
    {
        Schema::table('account_payments', function (Blueprint $table) {
            $table->dropColumn('payment_date');
        });
    }
};
