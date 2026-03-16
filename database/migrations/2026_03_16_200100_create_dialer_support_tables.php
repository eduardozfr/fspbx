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
        return BrazilianDialerStateRules::seedRows();
    }
};
