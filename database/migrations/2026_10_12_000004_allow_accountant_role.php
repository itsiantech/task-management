<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow the 'accountant' role on databases created before it was
     * added to the users.role enum. Fresh installs already include it.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                "SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role' LIMIT 1"
            );
            if ($row && ! str_contains((string) $row->t, 'accountant')) {
                DB::statement("ALTER TABLE users MODIFY role ENUM('admin','member','accountant') NOT NULL DEFAULT 'member'");
            }

            return;
        }

        if ($driver === 'sqlite') {
            $this->repairSqliteRoleCheck();
        }
    }

    private function repairSqliteRoleCheck(): void
    {
        $row = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'users'");
        $sql = (string) ($row->sql ?? '');

        if (str_contains($sql, 'accountant')) {
            return;
        }

        $newSql = preg_replace(
            "/in\s*\(\s*'admin'\s*,\s*'member'\s*\)/i",
            "in ('admin', 'member', 'accountant')",
            $sql,
            1,
            $count
        );

        if (! $count) {
            throw new \RuntimeException('Could not locate the users.role check constraint to repair it.');
        }

        $indexes = DB::select("SELECT sql FROM sqlite_master WHERE type = 'index' AND tbl_name = 'users' AND sql IS NOT NULL");
        $createBackup = preg_replace('/^(CREATE TABLE\s+)("?users"?)/i', '$1"users_backup"', $sql, 1);
        $createNew = preg_replace('/^(CREATE TABLE\s+)("?users"?)/i', '$1"users"', $newSql, 1);

        DB::statement('PRAGMA foreign_keys=OFF');
        try {
            DB::transaction(function () use ($createBackup, $createNew, $indexes) {
                DB::statement($createBackup);
                DB::statement('INSERT INTO "users_backup" SELECT * FROM "users"');
                DB::statement('DROP TABLE "users"');
                DB::statement($createNew);
                DB::statement('INSERT INTO "users" SELECT * FROM "users_backup"');
                DB::statement('DROP TABLE "users_backup"');
                foreach ($indexes as $index) {
                    DB::statement($index->sql);
                }
            });
        } finally {
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    public function down(): void
    {
        // Role widening is not rolled back automatically.
    }
};
