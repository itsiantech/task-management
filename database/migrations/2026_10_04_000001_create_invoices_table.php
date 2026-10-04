<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_number')->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['Draft', 'Paid', 'Unpaid', 'Due', 'Overdue', 'Cancelled', 'Pending Payment', 'Refunded'])->default('Draft');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->date('date_paid')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->date('refunded_date')->nullable();
            $table->enum('payment_method', ['cash_in', 'bkash', 'nagad', 'bank'])->default('bank');
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('advance_paid_credit', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_due', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
