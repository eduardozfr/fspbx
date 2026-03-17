<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_center_pause_reasons', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->string('code', 50);
            $table->string('name');
            $table->unsignedInteger('auto_resume_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('call_center_agent_pauses', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->uuid('call_center_agent_uuid')->index();
            $table->uuid('pause_reason_uuid')->nullable()->index();
            $table->uuid('created_by_user_uuid')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('call_center_callbacks', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->uuid('call_center_queue_uuid')->nullable()->index();
            $table->uuid('call_center_agent_uuid')->nullable()->index();
            $table->uuid('dialer_lead_uuid')->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('phone_number');
            $table->string('state_code', 2)->nullable();
            $table->string('timezone')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('preferred_callback_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('call_center_monitoring_sessions', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->uuid('call_center_agent_uuid')->nullable()->index();
            $table->uuid('supervisor_extension_uuid')->nullable()->index();
            $table->string('call_uuid')->index();
            $table->string('mode', 20);
            $table->string('status', 30)->default('queued');
            $table->string('freeswitch_job_uuid')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('call_center_events', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->uuid('call_center_queue_uuid')->nullable()->index();
            $table->uuid('call_center_agent_uuid')->nullable()->index();
            $table->string('call_uuid')->nullable()->index();
            $table->string('member_uuid')->nullable()->index();
            $table->string('direction', 20)->nullable();
            $table->string('event_type', 60)->index();
            $table->string('caller_number')->nullable();
            $table->unsignedInteger('wait_seconds')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        DB::table('call_center_pause_reasons')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'domain_uuid' => '00000000-0000-0000-0000-000000000000',
                'code' => 'break',
                'name' => 'Break',
                'auto_resume_minutes' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'domain_uuid' => '00000000-0000-0000-0000-000000000000',
                'code' => 'meeting',
                'name' => 'Meeting',
                'auto_resume_minutes' => 60,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'domain_uuid' => '00000000-0000-0000-0000-000000000000',
                'code' => 'training',
                'name' => 'Training',
                'auto_resume_minutes' => 120,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('call_center_events');
        Schema::dropIfExists('call_center_monitoring_sessions');
        Schema::dropIfExists('call_center_callbacks');
        Schema::dropIfExists('call_center_agent_pauses');
        Schema::dropIfExists('call_center_pause_reasons');
    }
};
