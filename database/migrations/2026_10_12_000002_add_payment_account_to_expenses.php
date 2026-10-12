<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('payment_account_id')->nullable()->after('paid_via')
                ->constrained('payment_accounts')->nullOnDelete();
            $table->index('payment_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['payment_account_id']);
            $table->dropIndex(['expenses_payment_account_id_index']);
            $table->dropColumn('payment_account_id');
        });
    }
};
