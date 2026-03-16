<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialer_leads', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company')->nullable();
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->string('external_ref')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialer_leads');
    }
};
