<?php

use App\Support\BrazilianDialerStateRules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialer_compliance_profiles', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('timezone')->nullable();
            $table->json('state_codes')->nullable();
            $table->json('schedule');
            $table->text('notes')->nullable();
            $table->string('legal_reference_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('dialer_campaigns', function (Blueprint $table) {
            $table->uuid('dialer_compliance_profile_uuid')->nullable()->after('call_center_queue_uuid');
        });

        DB::table('dialer_compliance_profiles')->insert([
            'uuid' => (string) Str::uuid(),
            'domain_uuid' => null,
            'name' => 'Anatel Baseline',
            'description' => 'Default outbound dialing baseline for Brazil, intended as a national starting point before operation-specific legal review.',
            'timezone' => null,
            'state_codes' => null,
            'schedule' => json_encode(BrazilianDialerStateRules::defaultSchedule(), JSON_THROW_ON_ERROR),
            'notes' => BrazilianDialerStateRules::note(),
            'legal_reference_url' => BrazilianDialerStateRules::referenceUrl(),
            'is_active' => true,
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('dialer_campaigns', function (Blueprint $table) {
            $table->dropColumn('dialer_compliance_profile_uuid');
        });

        Schema::dropIfExists('dialer_compliance_profiles');
    }
};
