<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialer_attempts', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('campaign_uuid')->index();
            $table->uuid('lead_uuid')->index();
            $table->uuid('extension_uuid')->nullable();
            $table->uuid('call_center_queue_uuid')->nullable();
            $table->string('mode', 20);
            $table->string('destination_number');
            $table->text('bridge_destination')->nullable();
            $table->string('freeswitch_job_uuid')->nullable();
            $table->string('disposition', 20)->default('queued');
            $table->text('notes')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialer_attempts');
    }
};
