<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialer_campaigns', function (Blueprint $table) {
            $table->string('default_state_code', 2)->nullable()->after('outbound_prefix');
            $table->string('default_timezone')->nullable()->after('default_state_code');
            $table->unsignedInteger('retry_backoff_minutes')->default(30)->after('max_attempts');
            $table->unsignedInteger('daily_retry_limit')->default(3)->after('retry_backoff_minutes');
            $table->boolean('respect_dnc')->default(true)->after('daily_retry_limit');
            $table->boolean('amd_enabled')->default(false)->after('respect_dnc');
            $table->string('amd_strategy', 50)->default('webhook')->after('amd_enabled');
            $table->string('webhook_url')->nullable()->after('amd_strategy');
            $table->string('webhook_secret')->nullable()->after('webhook_url');
            $table->string('callback_disposition_code', 50)->default('callback')->after('webhook_secret');
            $table->string('voicemail_disposition_code', 50)->default('voicemail')->after('callback_disposition_code');
        });

        Schema::table('dialer_leads', function (Blueprint $table) {
            $table->string('state_code', 2)->nullable()->after('phone_number');
            $table->string('timezone')->nullable()->after('state_code');
            $table->boolean('do_not_call')->default(false)->after('timezone');
            $table->string('last_disposition', 50)->nullable()->after('status');
            $table->timestamp('callback_requested_at')->nullable()->after('next_attempt_at');
            $table->timestamp('callback_due_at')->nullable()->after('callback_requested_at');
            $table->uuid('import_batch_uuid')->nullable()->after('callback_due_at');
        });

        Schema::table('dialer_campaign_leads', function (Blueprint $table) {
            $table->timestamp('next_attempt_at')->nullable()->after('last_attempt_at');
            $table->text('last_error')->nullable()->after('last_disposition');
            $table->timestamp('callback_due_at')->nullable()->after('last_error');
        });

        Schema::table('dialer_attempts', function (Blueprint $table) {
            $table->string('call_uuid')->nullable()->after('freeswitch_job_uuid');
            $table->uuid('disposition_uuid')->nullable()->after('disposition');
            $table->string('amd_result', 50)->nullable()->after('disposition_uuid');
            $table->string('hangup_cause', 120)->nullable()->after('amd_result');
            $table->unsignedInteger('wait_seconds')->default(0)->after('hangup_cause');
            $table->unsignedInteger('talk_seconds')->default(0)->after('wait_seconds');
            $table->timestamp('webhook_dispatched_at')->nullable()->after('completed_at');
            $table->json('metadata')->nullable()->after('webhook_dispatched_at');
        });
    }

    public function down(): void
    {
        Schema::table('dialer_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'call_uuid',
                'disposition_uuid',
                'amd_result',
                'hangup_cause',
                'wait_seconds',
                'talk_seconds',
                'webhook_dispatched_at',
                'metadata',
            ]);
        });

        Schema::table('dialer_campaign_leads', function (Blueprint $table) {
            $table->dropColumn([
                'next_attempt_at',
                'last_error',
                'callback_due_at',
            ]);
        });

        Schema::table('dialer_leads', function (Blueprint $table) {
            $table->dropColumn([
                'state_code',
                'timezone',
                'do_not_call',
                'last_disposition',
                'callback_requested_at',
                'callback_due_at',
                'import_batch_uuid',
            ]);
        });

        Schema::table('dialer_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'default_state_code',
                'default_timezone',
                'retry_backoff_minutes',
                'daily_retry_limit',
                'respect_dnc',
                'amd_enabled',
                'amd_strategy',
                'webhook_url',
                'webhook_secret',
                'callback_disposition_code',
                'voicemail_disposition_code',
            ]);
        });
    }
};
