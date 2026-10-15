<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The member-salary `payments` table already exists in this app, so the
        // accounts-side invoice payment ledger lives in its own table.
        Schema::rename('invoice_transactions', 'account_payments');

        Schema::table('account_payments', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete()->after('invoice_id');
            $table->foreignId('order_id')->nullable()->constrained('tasks')->nullOnDelete()->after('client_id');
            $table->enum('method', ['bkash', 'nagad', 'bank', 'cash'])->default('bank')->after('gateway');
            $table->string('receiving_account')->nullable()->after('method');
            $table->text('note')->nullable()->after('transaction_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('note');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');

            $table->index('method');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('account_payments', function (Blueprint $table) {
            $table->dropIndex(['account_payments_method_index']);
            $table->dropIndex(['account_payments_client_id_index']);
            $table->dropColumn(['client_id', 'order_id', 'method', 'receiving_account', 'note', 'created_by', 'updated_by']);
        });

        Schema::rename('account_payments', 'invoice_transactions');
    }
};