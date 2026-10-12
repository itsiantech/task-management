<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Change status enum - MySQL requires dropping and recreating
            $table->dropColumn('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', ['draft', 'unpaid', 'partial', 'paid', 'due', 'hold', 'void'])
                ->default('draft')
                ->after('task_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Add void and hold tracking fields
            $table->text('void_reason')->nullable()->after('notes');
            $table->timestamp('held_at')->nullable()->after('published_at');
            $table->foreignId('held_by')->nullable()->constrained('users')->nullOnDelete()->after('held_at');
            $table->timestamp('voided_at')->nullable()->after('held_by');
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete()->after('voided_at');

            // Add indexes
            $table->index('status');
            $table->index('invoice_date');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['void_reason', 'held_at', 'held_by', 'voided_at', 'voided_by']);
            $table->dropIndex(['status', 'invoice_date', 'due_date']);
            $table->dropColumn('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Revert status enum
            $table->enum('status', ['Draft', 'Paid', 'Unpaid', 'Due', 'Overdue', 'Cancelled', 'Pending Payment', 'Refunded'])
                ->default('Draft')
                ->after('task_id');
        });
    }
};
