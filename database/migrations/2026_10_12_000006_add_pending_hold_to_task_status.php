<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pending and Hold become first-class task statuses alongside the
     * existing workflow statuses. SQLite has no enum enforcement so only
     * MySQL/Postgres need the column alter. Existing rows are untouched.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending','not_started','in_progress','testing','awaiting_feedback','hold','completed') NOT NULL DEFAULT 'not_started'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE tasks ALTER COLUMN status TYPE VARCHAR(32)");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('not_started','in_progress','testing','awaiting_feedback','completed') NOT NULL DEFAULT 'not_started'");
        }
    }
};
