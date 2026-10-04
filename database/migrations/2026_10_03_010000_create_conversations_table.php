<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('platform');
            $table->string('sender_id');
            $table->string('sender_name')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');
            $table->dateTime('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'sender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
