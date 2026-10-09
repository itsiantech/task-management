<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('priority')->default('none')->after('status');
            $table->boolean('follow_up')->default(false)->after('priority');
            $table->boolean('unread')->default(false)->after('follow_up');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['priority', 'follow_up', 'unread']);
        });
    }
};
