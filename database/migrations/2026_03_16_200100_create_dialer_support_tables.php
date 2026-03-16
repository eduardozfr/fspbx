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
        Schema::create('dialer_dispositions', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->nullable()->index();
            $table->string('name');
            $table->string('code', 50)->index();
            $table->boolean('is_final')->default(false);
            $table->boolean('is_callback')->default(false);
            $table->boolean('mark_dnc')->default(false);
            $table->boolean('auto_close_lead')->default(false);
            $table->unsignedInteger('default_callback_offset_minutes')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('dialer_dnc_entries', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->nullable()->index();
            $table->string('phone_number');
            $table->string('normalized_phone')->index();
            $table->string('reason')->nullable();
            $table->string('source')->default('manual');
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('dialer_state_rules', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('state_code', 2)->unique();
            $table->string('state_name');
            $table->string('timezone');
            $table->json('schedule');
            $table->text('notes')->nullable();
            $table->string('legal_reference_url')->nullable();
            $table->timestamps();
        });

        Schema::create('dialer_import_batches', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->uuid('campaign_uuid')->nullable()->index();
            $table->uuid('user_uuid')->nullable()->index();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('status', 30)->default('queued');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->json('settings')->nullable();
            $table->json('errors')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        DB::table('dialer_dispositions')->insert($this->defaultDispositions());
        DB::table('dialer_state_rules')->insert($this->stateRules());
    }

    public function down(): void
    {
        Schema::dropIfExists('dialer_import_batches');
        Schema::dropIfExists('dialer_state_rules');
        Schema::dropIfExists('dialer_dnc_entries');
        Schema::dropIfExists('dialer_dispositions');
    }

    private function defaultDispositions(): array
    {
        $now = now();

        return [
            $this->disposition('Contacted', 'contacted', true, false, false, true, null, 'Successful live contact.'),
            $this->disposition('Sale', 'sale', true, false, false, true, null, 'Lead converted successfully.'),
            $this->disposition('No Answer', 'no-answer', false, false, false, false, 30, 'No one answered the call.'),
            $this->disposition('Busy', 'busy', false, false, false, false, 20, 'Busy tone or line in use.'),
            $this->disposition('Voicemail', 'voicemail', false, false, false, false, 180, 'Call reached voicemail or answering machine.'),
            $this->disposition('Callback Requested', 'callback', false, true, false, false, 60, 'Contact requested a callback.'),
            $this->disposition('Do Not Call', 'do-not-call', true, false, true, true, null, 'Do-not-call or opt-out request received.'),
            $this->disposition('Invalid Number', 'invalid-number', true, false, false, true, null, 'Destination number is invalid or disconnected.'),
            $this->disposition('Failed', 'failed', false, false, false, false, 60, 'Carrier or platform failure before conversation started.'),
        ];
    }

    private function disposition(
        string $name,
        string $code,
        bool $isFinal,
        bool $isCallback,
        bool $markDnc,
        bool $autoCloseLead,
        ?int $callbackOffsetMinutes,
        ?string $description
    ): array {
        return [
            'uuid' => (string) Str::uuid(),
            'domain_uuid' => null,
            'name' => $name,
            'code' => $code,
            'is_final' => $isFinal,
            'is_callback' => $isCallback,
            'mark_dnc' => $markDnc,
            'auto_close_lead' => $autoCloseLead,
            'default_callback_offset_minutes' => $callbackOffsetMinutes,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function stateRules(): array
    {
        $states = [
            ['AL', 'Alabama', 'America/Chicago'],
            ['AK', 'Alaska', 'America/Anchorage'],
            ['AZ', 'Arizona', 'America/Phoenix'],
            ['AR', 'Arkansas', 'America/Chicago'],
            ['CA', 'California', 'America/Los_Angeles'],
            ['CO', 'Colorado', 'America/Denver'],
            ['CT', 'Connecticut', 'America/New_York'],
            ['DE', 'Delaware', 'America/New_York'],
            ['DC', 'District of Columbia', 'America/New_York'],
            ['FL', 'Florida', 'America/New_York'],
            ['GA', 'Georgia', 'America/New_York'],
            ['HI', 'Hawaii', 'Pacific/Honolulu'],
            ['ID', 'Idaho', 'America/Boise'],
            ['IL', 'Illinois', 'America/Chicago'],
            ['IN', 'Indiana', 'America/Indiana/Indianapolis'],
            ['IA', 'Iowa', 'America/Chicago'],
            ['KS', 'Kansas', 'America/Chicago'],
            ['KY', 'Kentucky', 'America/New_York'],
            ['LA', 'Louisiana', 'America/Chicago'],
            ['ME', 'Maine', 'America/New_York'],
            ['MD', 'Maryland', 'America/New_York'],
            ['MA', 'Massachusetts', 'America/New_York'],
            ['MI', 'Michigan', 'America/Detroit'],
            ['MN', 'Minnesota', 'America/Chicago'],
            ['MS', 'Mississippi', 'America/Chicago'],
            ['MO', 'Missouri', 'America/Chicago'],
            ['MT', 'Montana', 'America/Denver'],
            ['NE', 'Nebraska', 'America/Chicago'],
            ['NV', 'Nevada', 'America/Los_Angeles'],
            ['NH', 'New Hampshire', 'America/New_York'],
            ['NJ', 'New Jersey', 'America/New_York'],
            ['NM', 'New Mexico', 'America/Denver'],
            ['NY', 'New York', 'America/New_York'],
            ['NC', 'North Carolina', 'America/New_York'],
            ['ND', 'North Dakota', 'America/Chicago'],
            ['OH', 'Ohio', 'America/New_York'],
            ['OK', 'Oklahoma', 'America/Chicago'],
            ['OR', 'Oregon', 'America/Los_Angeles'],
            ['PA', 'Pennsylvania', 'America/New_York'],
            ['RI', 'Rhode Island', 'America/New_York'],
            ['SC', 'South Carolina', 'America/New_York'],
            ['SD', 'South Dakota', 'America/Chicago'],
            ['TN', 'Tennessee', 'America/Chicago'],
            ['TX', 'Texas', 'America/Chicago'],
            ['UT', 'Utah', 'America/Denver'],
            ['VT', 'Vermont', 'America/New_York'],
            ['VA', 'Virginia', 'America/New_York'],
            ['WA', 'Washington', 'America/Los_Angeles'],
            ['WV', 'West Virginia', 'America/New_York'],
            ['WI', 'Wisconsin', 'America/Chicago'],
            ['WY', 'Wyoming', 'America/Denver'],
        ];

        $rows = [];

        foreach ($states as [$code, $name, $timezone]) {
            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'state_code' => $code,
                'state_name' => $name,
                'timezone' => $timezone,
                'schedule' => json_encode($this->scheduleForState($code), JSON_THROW_ON_ERROR),
                'notes' => $this->notesForState($code),
                'legal_reference_url' => $this->referenceForState($code),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    private function scheduleForState(string $stateCode): array
    {
        if ($stateCode === 'LA') {
            return [
                'monday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
                'tuesday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
                'wednesday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
                'thursday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
                'friday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
                'saturday' => ['enabled' => true, 'start' => '10:00', 'end' => '20:00'],
                'sunday' => ['enabled' => false, 'start' => null, 'end' => null],
            ];
        }

        return [
            'monday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
            'tuesday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
            'wednesday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
            'thursday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
            'friday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
            'saturday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
            'sunday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
        ];
    }

    private function notesForState(string $stateCode): string
    {
        return match ($stateCode) {
            'FL' => 'Conservative 08:00-20:00 local window aligned to stricter Florida telemarketing practice.',
            'WA' => 'Conservative 08:00-20:00 local window aligned to stricter Washington telemarketing practice.',
            'OK' => 'Conservative 08:00-20:00 local window aligned to stricter Oklahoma telemarketing practice.',
            'LA' => 'Louisiana uses a stricter schedule including a later Saturday start and Sunday block.',
            default => 'Conservative per-state local dialing window. Review with legal counsel before going live.',
        };
    }

    private function referenceForState(string $stateCode): string
    {
        return match ($stateCode) {
            'FL' => 'https://www.flsenate.gov/Laws/Statutes/2024/501.059',
            'WA' => 'https://www.atg.wa.gov/telemarketing',
            'OK' => 'https://oksenate.gov/press-releases/senate-bill-1500-signed-law-protect-oklahomans-telemarketers',
            'LA' => 'https://legis.la.gov/Legis/Law.aspx?d=50996',
            default => 'https://consumer.ftc.gov/business-guidance/resources/complying-telemarketing-sales-rule',
        };
    }
};
