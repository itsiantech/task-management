<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('created_at');
        });

        // invoice_number stores values like INV-2026-0001, so it must be a string.
        // SQLite (local dev) tolerates this via type affinity; MySQL/Postgres need an alter.
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY invoice_number VARCHAR(32) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE invoices ALTER COLUMN invoice_number TYPE VARCHAR(32)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
