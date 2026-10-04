<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assigned_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('lead_manager_id')->constrained('users')->cascadeOnDelete();
            $table->string('company')->nullable();
            $table->string('phone');
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['new', 'contacted', 'qualified', 'proposal_sent', 'won', 'lost'])->default('new');
            $table->dateTime('last_contact_date')->nullable();
            $table->dateTime('next_follow_up_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assigned_leads');
    }
};
