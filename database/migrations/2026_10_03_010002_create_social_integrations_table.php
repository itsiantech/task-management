<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_integrations', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['facebook', 'whatsapp']);
            $table->string('account_name')->nullable();
            $table->string('page_id_or_phone_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['platform', 'account_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_integrations');
    }
};
