<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seen/unseen receipts for task chat. A message counts as seen once
     * any other participant opens the conversation.
     */
    public function up(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            if (! Schema::hasColumn('task_comments', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            if (Schema::hasColumn('task_comments', 'read_at')) {
                $table->dropColumn('read_at');
            }
        });
    }
};
