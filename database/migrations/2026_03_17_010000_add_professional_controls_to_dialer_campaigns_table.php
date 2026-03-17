<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialer_campaigns', function (Blueprint $table) {
            $table->unsignedInteger('max_inflight_calls')->nullable()->after('pacing_ratio');
            $table->string('busy_disposition_code', 50)->default('busy')->after('voicemail_disposition_code');
            $table->string('no_answer_disposition_code', 50)->default('no-answer')->after('busy_disposition_code');
            $table->string('invalid_number_disposition_code', 50)->default('invalid-number')->after('no_answer_disposition_code');
            $table->string('voicemail_action', 20)->default('hangup')->after('invalid_number_disposition_code');
        });
    }

    public function down(): void
    {
        Schema::table('dialer_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'max_inflight_calls',
                'busy_disposition_code',
                'no_answer_disposition_code',
                'invalid_number_disposition_code',
                'voicemail_action',
            ]);
        });
    }
};
