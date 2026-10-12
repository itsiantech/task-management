<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair accounts tables on databases that already ran the
     * Phase-2 migrations before their fixes (dev databases).
     * Fresh installs are unaffected (checks are idempotent).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('expenses', 'deleted_at')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if ($this->paymentEditsReferencesWrongTable()) {
            Schema::dropIfExists('payment_edits');

            Schema::create('payment_edits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('account_payments')->cascadeOnDelete();
                $table->foreignId('edited_by')->constrained('users')->cascadeOnDelete();
                $table->timestamp('edited_at');
                $table->json('old_values');
                $table->json('new_values');
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->index('payment_id');
                $table->index('edited_at');
            });
        }
    }

    private function paymentEditsReferencesWrongTable(): bool
    {
        if (! Schema::hasTable('payment_edits')) {
            return false;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $row = DB::selectOne("SELECT sql FROM sqlite_master WHERE name = 'payment_edits'");
            $sql = (string) ($row->sql ?? '');

            return str_contains($sql, '"payments"') || str_contains($sql, '`payments`') || str_contains($sql, '(payments)');
        }

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                "SELECT REFERENCED_TABLE_NAME AS t FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_edits'
                 AND COLUMN_NAME = 'payment_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1"
            );

            return (($row->t ?? null) === 'payments');
        }

        return false;
    }

    public function down(): void
    {
        // Repair only; nothing to roll back.
    }
};
