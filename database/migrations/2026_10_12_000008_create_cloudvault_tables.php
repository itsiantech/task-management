<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloudvault_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('color', 20)->default('#0f766e');
            $table->boolean('public_link')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('cloudvault_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('cloudvault_sheets')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['domain', 'dropdown', 'password', 'email', 'text', 'note'])->default('text');
            $table->unsignedInteger('width')->default(170);
            $table->string('header_bg', 20)->default('#0f766e');
            $table->string('header_color', 20)->default('#ffffff');
            $table->unsignedTinyInteger('header_size')->default(14);
            $table->enum('header_align', ['left', 'center', 'right'])->default('left');
            $table->boolean('header_bold')->default(true);
            $table->json('options')->nullable();
            $table->boolean('sticky')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index('sheet_id');
        });

        Schema::create('cloudvault_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('cloudvault_sheets')->cascadeOnDelete();
            $table->json('cells');
            $table->enum('status', ['Active', 'Deactive', 'Suspended'])->default('Active');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['sheet_id', 'status']);
        });

        Schema::create('cloudvault_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('cloudvault_sheets')->cascadeOnDelete();
            $table->string('email');
            $table->enum('role', ['Admin', 'Editor', 'Viewer'])->default('Viewer');
            $table->timestamps();
            $table->unique(['sheet_id', 'email']);
        });

        Schema::create('cloudvault_row_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('cloudvault_sheets')->cascadeOnDelete();
            $table->foreignId('row_id')->constrained('cloudvault_rows')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloudvault_row_shares');
        Schema::dropIfExists('cloudvault_shares');
        Schema::dropIfExists('cloudvault_rows');
        Schema::dropIfExists('cloudvault_columns');
        Schema::dropIfExists('cloudvault_sheets');
    }
};
