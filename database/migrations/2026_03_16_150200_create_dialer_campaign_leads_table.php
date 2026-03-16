<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialer_campaign_leads', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('campaign_uuid')->index();
            $table->uuid('lead_uuid')->index();
            $table->unsignedInteger('priority')->default(100);
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('attempts_count')->default(0);
            $table->uuid('assigned_extension_uuid')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->string('last_disposition')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialer_campaign_leads');
    }
};
