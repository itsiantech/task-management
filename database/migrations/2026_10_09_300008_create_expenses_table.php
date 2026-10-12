<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['daily', 'ads', 'server', 'family', 'office', 'marketing', 'salary', 'tools', 'investor', 'custom']);
            $table->string('custom_category')->nullable();
            $table->string('title');
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('expense_date');
            $table->enum('paid_via', ['bkash', 'nagad', 'bank', 'cash'])->default('cash');
            $table->string('paid_to')->nullable();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->string('attachment')->nullable();
            $table->decimal('payable_amount', 15, 2)->default(0);
            $table->boolean('is_paid')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('expense_date');
            $table->index('paid_via');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
