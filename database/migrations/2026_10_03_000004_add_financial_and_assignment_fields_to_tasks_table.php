<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('total_budget', 12, 2)->default(0)->after('description');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_budget');
            $table->decimal('due_amount', 12, 2)->default(0)->after('paid_amount');
            $table->enum('payment_status', ['pending', 'partial', 'complete', 'hold', 'cancelled'])
                ->default('pending')
                ->after('due_amount');
        });

        $driver = DB::getDriverName();

        if (! in_array($driver, ['sqlite'], true)) {
            DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'hold', 'cancelled') NOT NULL DEFAULT 'pending'");
        }

        Schema::create('task_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('month_year');
            $table->enum('payment_status', ['complete', 'hold', 'cancelled'])->default('complete');
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->timestamps();
        });

        $tasks = DB::table('tasks')->get();
        foreach ($tasks as $task) {
            $dueAmount = max(0, (float) $task->total_budget - (float) $task->paid_amount);
            $paymentStatus = 'pending';

            if ((float) $task->paid_amount > 0 && $dueAmount > 0) {
                $paymentStatus = 'partial';
            } elseif ((float) $task->paid_amount >= (float) $task->total_budget && (float) $task->total_budget > 0) {
                $paymentStatus = 'complete';
            }

            DB::table('tasks')->where('id', $task->id)->update([
                'due_amount' => number_format($dueAmount, 2, '.', ''),
                'payment_status' => $paymentStatus,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('task_user');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['total_budget', 'paid_amount', 'due_amount', 'payment_status']);
        });

        $driver = DB::getDriverName();

        if (! in_array($driver, ['sqlite'], true)) {
            DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending'");
        }
    }
};
