<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialer_campaigns', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('mode', 20)->default('manual');
            $table->string('status', 20)->default('draft');
            $table->string('caller_id_name')->nullable();
            $table->string('caller_id_number')->nullable();
            $table->string('outbound_prefix')->nullable();
            $table->uuid('call_center_queue_uuid')->nullable();
            $table->decimal('pacing_ratio', 5, 2)->default(1.00);
            $table->unsignedInteger('preview_seconds')->default(30);
            $table->unsignedInteger('originate_timeout')->default(30);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialer_campaigns');
    }
};
