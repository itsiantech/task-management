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
            if (! Schema::hasColumn('tasks', 'start_date')) {
                $table->date('start_date')->nullable()->after('status');
            }

            if (! Schema::hasColumn('tasks', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->after('due_date');
            }

            if (! Schema::hasColumn('tasks', 'tags')) {
                $table->json('tags')->nullable()->after('priority');
            }
        });

        $driver = DB::getDriverName();

        if (! in_array($driver, ['sqlite'], true)) {
            DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('not_started', 'in_progress', 'testing', 'awaiting_feedback', 'completed') NOT NULL DEFAULT 'not_started'");
        }

        DB::table('tasks')->whereIn('status', ['pending', 'cancelled'])->update(['status' => 'not_started']);
        DB::table('tasks')->where('status', 'hold')->update(['status' => 'awaiting_feedback']);
        DB::table('tasks')->where('status', 'completed')->update(['status' => 'completed']);

        DB::table('tasks')->whereNull('priority')->update(['priority' => 'medium']);
        DB::table('tasks')->whereNull('start_date')->whereNotNull('due_date')->update(['start_date' => DB::raw('due_date')]);
        DB::table('tasks')->whereNull('tags')->update(['tags' => '[]']);
    }

    public function down(): void
    {
        DB::table('tasks')->whereIn('status', ['not_started', 'testing', 'awaiting_feedback'])->update(['status' => 'pending']);
        DB::table('tasks')->where('status', 'in_progress')->update(['status' => 'in_progress']);
        DB::table('tasks')->where('status', 'completed')->update(['status' => 'completed']);

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'start_date')) {
                $table->dropColumn('start_date');
            }

            if (Schema::hasColumn('tasks', 'priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('tasks', 'tags')) {
                $table->dropColumn('tags');
            }
        });

        $driver = DB::getDriverName();

        if (! in_array($driver, ['sqlite'], true)) {
            DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'hold', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
