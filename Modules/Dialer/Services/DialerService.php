<?php

namespace Modules\Dialer\Services;

use App\Models\CallCenterQueues;
use App\Models\Domain;
use App\Models\Extensions;
use App\Services\FreeswitchEslService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\CallCenter\Models\CallCenterCallback;
use Modules\Dialer\Events\DialerCampaignUpdated;
use Modules\Dialer\Jobs\DispatchDialerWebhookJob;
use Modules\Dialer\Models\DialerAttempt;
use Modules\Dialer\Models\DialerCampaign;
use Modules\Dialer\Models\DialerCampaignLead;
use Modules\Dialer\Models\DialerComplianceProfile;
use Modules\Dialer\Models\DialerDisposition;
use Modules\Dialer\Models\DialerDncEntry;
use Modules\Dialer\Models\DialerImportBatch;
use Modules\Dialer\Models\DialerLead;
use Modules\Dialer\Models\DialerStateRule;
use RuntimeException;
use Throwable;

class DialerService
{
    public function __construct(
        protected DialerComplianceService $compliance
    ) {}

    public function createCampaign(string $domainUuid, array $payload): DialerCampaign
    {
        return DB::transaction(function () use ($domainUuid, $payload) {
            $attributes = $this->prepareCampaignAttributes($domainUuid, $payload);

            return DialerCampaign::query()->create($attributes);
        });
    }

    public function updateCampaign(DialerCampaign $campaign, array $payload): DialerCampaign
    {
        return DB::transaction(function () use ($campaign, $payload) {
            $campaign->update($this->prepareCampaignAttributes($campaign->domain_uuid, $payload));

            return $campaign->fresh(['queue', 'complianceProfile']);
        });
    }

    public function nextLead(DialerCampaign $campaign): ?DialerCampaignLead
    {
        $candidates = DialerCampaignLead::query()
            ->with('lead')
            ->where('campaign_uuid', $campaign->uuid)
            ->whereIn('status', ['queued', 'retrying', 'callback'])
            ->where('attempts_count', '<', $campaign->max_attempts)
            ->where(function ($query) {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', now());
            })
            ->orderBy('priority')
            ->orderByRaw('COALESCE(next_attempt_at, created_at)')
            ->limit(100)
            ->get();

        foreach ($candidates as $campaignLead) {
            $evaluation = $this->evaluateCampaignLead($campaign, $campaignLead);

            if ($evaluation['allowed']) {
                return $campaignLead;
            }

            if ($evaluation['final']) {
                $this->blockCampaignLead($campaignLead, $evaluation['reason']);
                continue;
            }

            if ($evaluation['next_callable_at']) {
                $this->deferCampaignLead($campaignLead, $evaluation['next_callable_at'], $evaluation['reason']);
            }
        }

        return null;
    }

    public function startManualCall(DialerCampaign $campaign, DialerCampaignLead $campaignLead, Extensions $extension): array
    {
        $campaignLead->loadMissing('lead');
        $this->assertCallable($campaign, $campaignLead);

        return DB::transaction(function () use ($campaign, $campaignLead, $extension) {
            $bridgeDestination = $this->resolveBridgeDestination(
                $campaign->domain_uuid,
                (string) ($campaign->outbound_prefix . $campaignLead->lead->phone_number)
            );

            $attempt = $this->createAttempt($campaign, $campaignLead, $extension->extension_uuid, $bridgeDestination);

            $command = sprintf(
                "bgapi originate {%s}user/%s@%s &bridge(%s)",
                $this->buildChannelVariables($campaign, $campaignLead, $attempt, $extension->extension_uuid),
                $extension->extension,
                $extension->user_context,
                $bridgeDestination
            );

            $response = (new FreeswitchEslService())->executeCommand($command);
            $jobUuid = is_array($response) ? ($response['job_uuid'] ?? null) : null;

            $attempt->update(['freeswitch_job_uuid' => $jobUuid]);
            $this->markLeadDialed($campaignLead, 'calling');
            $this->notifyCampaignChanged($campaign, 'dialer.call.dispatched', [
                'lead_uuid' => $campaignLead->lead_uuid,
                'attempt_uuid' => $attempt->uuid,
                'job_uuid' => $jobUuid,
                'mode' => 'manual',
            ]);

            return [
                'attempt' => $attempt->fresh(),
                'job_uuid' => $jobUuid,
            ];
        });
    }

    public function processCampaign(DialerCampaign $campaign): array
    {
        $campaign->loadMissing('queue');

        if (! in_array($campaign->mode, ['progressive', 'power'], true)) {
            return ['dialed' => 0];
        }

        if (! $campaign->queue) {
            throw new RuntimeException('This campaign requires a call center queue.');
        }

        $availableAgents = max(1, $this->countReadyAgents($campaign->queue));
        $dialCount = $campaign->mode === 'power'
            ? max(1, (int) ceil($availableAgents * (float) $campaign->pacing_ratio))
            : $availableAgents;

        $dialed = 0;

        for ($index = 0; $index < $dialCount; $index++) {
            $campaignLead = $this->nextLead($campaign);

            if (! $campaignLead) {
                break;
            }

            $this->startQueueCall($campaign, $campaignLead);
            $dialed++;
        }

        $campaign->update(['last_executed_at' => now()]);
        $this->notifyCampaignChanged($campaign, 'dialer.cycle.processed', ['dialed' => $dialed]);

        return ['dialed' => $dialed];
    }

    public function createCampaignLeadAssignments(DialerLead $lead, array $campaignUuids): void
    {
        foreach ($campaignUuids as $campaignUuid) {
            DialerCampaignLead::query()->firstOrCreate(
                [
                    'campaign_uuid' => $campaignUuid,
                    'lead_uuid' => $lead->uuid,
                ],
                [
                    'priority' => 100,
                    'status' => $lead->do_not_call ? 'blocked' : 'queued',
                    'attempts_count' => 0,
                ]
            );
        }
    }

    public function recordDisposition(DialerAttempt $attempt, array $payload, bool $fromWebhook = false): DialerAttempt
    {
        $attempt->loadMissing(['campaign', 'lead']);
        $campaign = $attempt->campaign;
        $lead = $attempt->lead;

        if (! $campaign || ! $lead) {
            throw new RuntimeException('Unable to resolve the attempt campaign or lead.');
        }

        $disposition = $this->resolveDisposition($campaign->domain_uuid, $payload);

        if (! $disposition) {
            throw new RuntimeException('Select a valid disposition.');
        }

        $callbackAt = $payload['preferred_callback_at'] ?? null;
        $callbackAt = $callbackAt ? CarbonImmutable::parse($callbackAt) : null;

        if (! $callbackAt && $disposition->is_callback && $disposition->default_callback_offset_minutes) {
            $callbackAt = $this->scheduleRetryAt($campaign, $lead, now(), $disposition->default_callback_offset_minutes);
        }

        $attempt->fill([
            'disposition' => $disposition->code,
            'disposition_uuid' => $disposition->uuid,
            'notes' => $payload['notes'] ?? $attempt->notes,
            'amd_result' => $payload['amd_result'] ?? $attempt->amd_result,
            'hangup_cause' => $payload['hangup_cause'] ?? $attempt->hangup_cause,
            'answered_at' => $payload['answered_at'] ?? $attempt->answered_at,
            'completed_at' => $payload['completed_at'] ?? now(),
            'talk_seconds' => (int) ($payload['talk_seconds'] ?? $attempt->talk_seconds ?? 0),
            'wait_seconds' => (int) ($payload['wait_seconds'] ?? $attempt->wait_seconds ?? 0),
            'metadata' => array_filter(array_merge($attempt->metadata ?? [], $payload['metadata'] ?? [])),
        ])->save();

        $campaignLead = DialerCampaignLead::query()
            ->where('campaign_uuid', $attempt->campaign_uuid)
            ->where('lead_uuid', $attempt->lead_uuid)
            ->first();

        $nextAttemptAt = null;
        $campaignLeadStatus = 'completed';
        $leadStatus = $disposition->code;

        if ($disposition->mark_dnc) {
            $this->markLeadAsDnc($lead, $payload['notes'] ?? $disposition->description, 'disposition');
            $campaignLeadStatus = 'blocked';
        } elseif ($disposition->is_callback) {
            $nextAttemptAt = $callbackAt ?: $this->scheduleRetryAt($campaign, $lead);
            $campaignLeadStatus = 'callback';
            $leadStatus = 'callback';
            $this->createCallbackFromDisposition($attempt, $lead, $callbackAt, $payload);
        } elseif (! $disposition->is_final) {
            $nextAttemptAt = $this->scheduleRetryAt($campaign, $lead, now(), $campaign->retry_backoff_minutes);
            $campaignLeadStatus = 'retrying';
            $leadStatus = 'queued';
        }

        if ($campaignLead) {
            $campaignLead->update([
                'status' => $campaignLeadStatus,
                'last_disposition' => $disposition->code,
                'last_error' => $payload['notes'] ?? null,
                'next_attempt_at' => $nextAttemptAt,
                'callback_due_at' => $callbackAt,
            ]);
        }

        $lead->update([
            'status' => $leadStatus,
            'last_disposition' => $disposition->code,
            'last_attempt_at' => now(),
            'next_attempt_at' => $nextAttemptAt,
            'callback_requested_at' => $disposition->is_callback ? now() : $lead->callback_requested_at,
            'callback_due_at' => $callbackAt,
        ]);

        $this->notifyCampaignChanged($campaign, 'dialer.disposition.updated', [
            'attempt_uuid' => $attempt->uuid,
            'lead_uuid' => $lead->uuid,
            'disposition' => $disposition->code,
            'status' => $campaignLeadStatus,
            'from_webhook' => $fromWebhook,
        ]);

        return $attempt->fresh(['campaign', 'lead', 'dispositionModel']);
    }

    public function syncAttemptFromPayload(array $payload): ?DialerAttempt
    {
        $attempt = $this->resolveAttemptFromPayload($payload);

        if (! $attempt) {
            return null;
        }

        $metadata = array_filter(array_merge($attempt->metadata ?? [], [
            'event' => data_get($payload, 'event'),
            'payload' => data_get($payload, 'data', $payload),
        ]));

        $attempt->fill([
            'call_uuid' => data_get($payload, 'data.call_uuid', data_get($payload, 'call_uuid', $attempt->call_uuid)),
            'freeswitch_job_uuid' => data_get($payload, 'data.job_uuid', data_get($payload, 'job_uuid', $attempt->freeswitch_job_uuid)),
            'amd_result' => data_get($payload, 'data.amd_result', data_get($payload, 'amd_result', $attempt->amd_result)),
            'hangup_cause' => data_get($payload, 'data.hangup_cause', data_get($payload, 'hangup_cause', $attempt->hangup_cause)),
            'answered_at' => data_get($payload, 'data.answered_at', data_get($payload, 'answered_at', $attempt->answered_at)),
            'completed_at' => data_get($payload, 'data.completed_at', data_get($payload, 'completed_at', $attempt->completed_at)),
            'talk_seconds' => (int) data_get($payload, 'data.talk_seconds', data_get($payload, 'talk_seconds', $attempt->talk_seconds ?? 0)),
            'wait_seconds' => (int) data_get($payload, 'data.wait_seconds', data_get($payload, 'wait_seconds', $attempt->wait_seconds ?? 0)),
            'metadata' => $metadata,
        ])->save();

        $dispositionCode = data_get($payload, 'data.disposition', data_get($payload, 'disposition'));

        if (! $dispositionCode) {
            $dispositionCode = $this->inferDispositionFromPayload($attempt, $payload);
        }

        if ($dispositionCode) {
            return $this->recordDisposition($attempt, [
                'disposition_code' => $dispositionCode,
                'notes' => data_get($payload, 'data.notes', data_get($payload, 'notes')),
                'amd_result' => $attempt->amd_result,
                'hangup_cause' => $attempt->hangup_cause,
                'answered_at' => $attempt->answered_at,
                'completed_at' => $attempt->completed_at ?? now(),
                'talk_seconds' => $attempt->talk_seconds,
                'wait_seconds' => $attempt->wait_seconds,
                'metadata' => $metadata,
            ], true);
        }

        if ($attempt->campaign) {
            $this->notifyCampaignChanged($attempt->campaign, 'dialer.attempt.synced', [
                'attempt_uuid' => $attempt->uuid,
                'lead_uuid' => $attempt->lead_uuid,
                'amd_result' => $attempt->amd_result,
            ]);
        }

        return $attempt->fresh();
    }

    public function importLeadRow(DialerImportBatch $batch, array $row, int $rowNumber): DialerLead
    {
        $normalized = $this->normalizeImportRow($row);
        $campaignUuids = Arr::wrap(data_get($batch->settings, 'campaign_uuids', []));
        $campaignUuids = array_values(array_filter(array_map('strval', $campaignUuids)));

        if (empty($campaignUuids)) {
            throw new RuntimeException('The import batch does not contain any target campaigns.');
        }

        if (empty($normalized['phone_number'])) {
            throw new RuntimeException("Row {$rowNumber} is missing a phone number.");
        }

        $lead = DialerLead::query()->create([
            'domain_uuid' => $batch->domain_uuid,
            'first_name' => $normalized['first_name'] ?? null,
            'last_name' => $normalized['last_name'] ?? null,
            'company' => $normalized['company'] ?? null,
            'phone_number' => $normalized['phone_number'],
            'email' => $normalized['email'] ?? null,
            'state_code' => strtoupper($normalized['state_code'] ?? data_get($batch->settings, 'default_state_code', '')) ?: null,
            'timezone' => $normalized['timezone'] ?? data_get($batch->settings, 'default_timezone'),
            'external_ref' => $normalized['external_ref'] ?? null,
            'metadata' => $normalized['metadata'] ?? $row,
            'status' => ! empty($normalized['do_not_call']) ? 'blocked' : 'new',
            'do_not_call' => ! empty($normalized['do_not_call']),
            'import_batch_uuid' => $batch->uuid,
        ]);

        if ($lead->do_not_call) {
            $this->markLeadAsDnc($lead, 'Imported with do-not-call flag', 'import');
        }

        $this->createCampaignLeadAssignments($lead, $campaignUuids);

        return $lead;
    }

    public function evaluateCampaignLead(DialerCampaign $campaign, DialerCampaignLead $campaignLead, ?CarbonInterface $moment = null): array
    {
        $campaignLead->loadMissing('lead');
        $lead = $campaignLead->lead;

        if (! $lead) {
            return $this->evaluation(false, 'Lead record not found.', null, true);
        }

        if ($lead->do_not_call || ($campaign->respect_dnc && $this->isPhoneOnDnc($lead->phone_number, $campaign->domain_uuid))) {
            return $this->evaluation(false, 'Lead is on the do-not-call list.', null, true);
        }

        if ($campaignLead->attempts_count >= $campaign->max_attempts) {
            return $this->evaluation(false, 'Maximum attempts reached for this lead.', null, true);
        }

        $callbackDueAt = $campaignLead->callback_due_at ?: $lead->callback_due_at;
        $moment = $moment ? CarbonImmutable::instance($moment) : CarbonImmutable::now();

        if ($callbackDueAt && CarbonImmutable::parse($callbackDueAt)->greaterThan($moment)) {
            return $this->evaluation(false, 'Lead is waiting for a scheduled callback window.', CarbonImmutable::parse($callbackDueAt));
        }

        $timezone = $this->resolveTimezone($campaign, $lead);
        $schedule = $this->resolveComplianceSchedule($campaign, $lead);
        $scheduledMoment = $moment->setTimezone($timezone);

        $dailyAttempts = DialerAttempt::query()
            ->where('campaign_uuid', $campaign->uuid)
            ->where('lead_uuid', $lead->uuid)
            ->where('created_at', '>=', $scheduledMoment->copy()->startOfDay()->setTimezone(config('app.timezone')))
            ->count();

        if ($dailyAttempts >= $campaign->daily_retry_limit) {
            $nextDay = $scheduledMoment->addDay()->setTime(8, 0);
            $nextCallable = $this->compliance->nextCallableAt($schedule, $timezone, $nextDay) ?: $nextDay;

            return $this->evaluation(false, 'Daily retry limit reached for this lead.', $nextCallable);
        }

        $evaluation = $this->compliance->evaluate($schedule, $timezone, $scheduledMoment);

        if (! $evaluation['allowed']) {
            return $this->evaluation(false, 'Dialing is outside the allowed local window for this lead.', $evaluation['next_callable_at']);
        }

        return $this->evaluation(true, null, $evaluation['next_callable_at']);
    }

    public function isPhoneOnDnc(string $phoneNumber, ?string $domainUuid = null): bool
    {
        $normalized = $this->normalizePhone($phoneNumber);

        return DialerDncEntry::query()
            ->where('normalized_phone', $normalized)
            ->where(function ($query) use ($domainUuid) {
                $query->whereNull('domain_uuid');

                if ($domainUuid) {
                    $query->orWhere('domain_uuid', $domainUuid);
                }
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function scheduleRetryAt(
        DialerCampaign $campaign,
        DialerLead $lead,
        CarbonInterface|string|null $moment = null,
        ?int $backoffMinutes = null
    ): ?CarbonImmutable {
        $timezone = $this->resolveTimezone($campaign, $lead);
        $seed = $moment
            ? CarbonImmutable::parse((string) $moment)->setTimezone($timezone)
            : CarbonImmutable::now($timezone);

        $seed = $seed->addMinutes($backoffMinutes ?? $campaign->retry_backoff_minutes);
        $evaluation = $this->compliance->evaluate($this->resolveComplianceSchedule($campaign, $lead), $timezone, $seed);

        return $evaluation['allowed']
            ? $seed
            : $evaluation['next_callable_at'];
    }

    public function normalizePhone(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber);

        if ($digits === '') {
            return $phoneNumber;
        }

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+' . $digits;
        }

        return str_starts_with($phoneNumber, '+') ? '+' . $digits : $digits;
    }

    public function prepareCampaignAttributes(string $domainUuid, array $payload): array
    {
        $attributes = [
            'domain_uuid' => $domainUuid,
            'name' => trim((string) ($payload['name'] ?? '')),
            'description' => $this->blankToNull($payload['description'] ?? null),
            'mode' => $payload['mode'] ?? 'manual',
            'status' => $payload['status'] ?? 'draft',
            'caller_id_name' => $this->blankToNull($payload['caller_id_name'] ?? null),
            'caller_id_number' => $this->blankToNull($payload['caller_id_number'] ?? null),
            'outbound_prefix' => $this->blankToNull($payload['outbound_prefix'] ?? null),
            'call_center_queue_uuid' => $this->blankToNull($payload['call_center_queue_uuid'] ?? null),
            'dialer_compliance_profile_uuid' => $this->blankToNull($payload['dialer_compliance_profile_uuid'] ?? null),
            'default_state_code' => strtoupper((string) $this->blankToNull($payload['default_state_code'] ?? null)) ?: null,
            'default_timezone' => $this->blankToNull($payload['default_timezone'] ?? null),
            'pacing_ratio' => (float) ($payload['pacing_ratio'] ?? 1),
            'preview_seconds' => (int) ($payload['preview_seconds'] ?? 30),
            'originate_timeout' => (int) ($payload['originate_timeout'] ?? 30),
            'max_attempts' => (int) ($payload['max_attempts'] ?? 3),
            'retry_backoff_minutes' => (int) ($payload['retry_backoff_minutes'] ?? 30),
            'daily_retry_limit' => (int) ($payload['daily_retry_limit'] ?? 3),
            'respect_dnc' => (bool) ($payload['respect_dnc'] ?? true),
            'amd_enabled' => (bool) ($payload['amd_enabled'] ?? false),
            'amd_strategy' => $this->blankToNull($payload['amd_strategy'] ?? null) ?: 'webhook',
            'webhook_url' => $this->blankToNull($payload['webhook_url'] ?? null),
            'webhook_secret' => $this->blankToNull($payload['webhook_secret'] ?? null),
            'callback_disposition_code' => $this->blankToNull($payload['callback_disposition_code'] ?? null) ?: 'callback',
            'voicemail_disposition_code' => $this->blankToNull($payload['voicemail_disposition_code'] ?? null) ?: 'voicemail',
        ];

        if (in_array($attributes['mode'], ['progressive', 'power'], true) && ! $attributes['call_center_queue_uuid']) {
            throw new RuntimeException('Select a queue before activating progressive or power dialing.');
        }

        if ($attributes['call_center_queue_uuid']) {
            $queueExists = CallCenterQueues::query()
                ->where('domain_uuid', $domainUuid)
                ->where('call_center_queue_uuid', $attributes['call_center_queue_uuid'])
                ->exists();

            if (! $queueExists) {
                throw new RuntimeException('Select a valid queue for this campaign.');
            }
        }

        if ($attributes['dialer_compliance_profile_uuid']) {
            $profileExists = DialerComplianceProfile::query()
                ->where('uuid', $attributes['dialer_compliance_profile_uuid'])
                ->where('is_active', true)
                ->where(function ($query) use ($domainUuid) {
                    $query->whereNull('domain_uuid')
                        ->orWhere('domain_uuid', $domainUuid);
                })
                ->exists();

            if (! $profileExists) {
                throw new RuntimeException('Select a valid compliance profile for this campaign.');
            }
        }

        return $attributes;
    }

    protected function startQueueCall(DialerCampaign $campaign, DialerCampaignLead $campaignLead): void
    {
        $campaignLead->loadMissing('lead');
        $this->assertCallable($campaign, $campaignLead);

        $domain = Domain::query()
            ->where('domain_uuid', $campaign->domain_uuid)
            ->first();

        $bridgeDestination = $this->resolveBridgeDestination(
            $campaign->domain_uuid,
            (string) ($campaign->outbound_prefix . $campaignLead->lead->phone_number)
        );

        $attempt = $this->createAttempt($campaign, $campaignLead, null, $bridgeDestination);

        $command = sprintf(
            "bgapi originate {%s}%s &transfer('%s XML %s')",
            $this->buildChannelVariables($campaign, $campaignLead, $attempt, null),
            $bridgeDestination,
            $campaign->queue->queue_extension,
            $domain?->domain_name ?? session('domain_name')
        );

        $response = (new FreeswitchEslService())->executeCommand($command);
        $jobUuid = is_array($response) ? ($response['job_uuid'] ?? null) : null;

        $attempt->update(['freeswitch_job_uuid' => $jobUuid]);
        $this->markLeadDialed($campaignLead, 'calling');
        $this->notifyCampaignChanged($campaign, 'dialer.call.dispatched', [
            'lead_uuid' => $campaignLead->lead_uuid,
            'attempt_uuid' => $attempt->uuid,
            'job_uuid' => $jobUuid,
            'mode' => $campaign->mode,
        ]);
    }

    protected function buildChannelVariables(
        DialerCampaign $campaign,
        DialerCampaignLead $campaignLead,
        DialerAttempt $attempt,
        ?string $extensionUuid
    ): string {
        $parts = [
            'originate_timeout=' . (int) ($campaign->originate_timeout ?? 30),
            'ignore_early_media=true',
            'hangup_after_bridge=true',
            "call_direction='outbound'",
            "origination_caller_id_name='" . addslashes($campaign->caller_id_name ?: $campaign->name) . "'",
            "origination_caller_id_number='" . addslashes($campaign->caller_id_number ?: '') . "'",
            'dialer_campaign_uuid=' . $campaign->uuid,
            'dialer_lead_uuid=' . $campaignLead->lead_uuid,
            'dialer_attempt_uuid=' . $attempt->uuid,
            'dialer_mode=' . $campaign->mode,
        ];

        if ($extensionUuid) {
            $parts[] = 'dialer_extension_uuid=' . $extensionUuid;
        }

        if ($campaign->amd_enabled) {
            $parts[] = 'dialer_amd_enabled=true';
            $parts[] = "execute_on_answer='avmd_start'";
        }

        return implode(',', $parts);
    }

    protected function resolveBridgeDestination(string $domainUuid, string $destinationNumber): string
    {
        $routes = outbound_route_to_bridge($domainUuid, $destinationNumber);

        if (empty($routes[0])) {
            throw new RuntimeException('No outbound route matched the requested destination.');
        }

        return $routes[0];
    }

    protected function markLeadDialed(DialerCampaignLead $campaignLead, string $status): void
    {
        $campaignLead->update([
            'status' => $status,
            'attempts_count' => $campaignLead->attempts_count + 1,
            'last_attempt_at' => now(),
        ]);

        DialerLead::query()
            ->whereKey($campaignLead->lead_uuid)
            ->update([
                'status' => $status,
                'last_attempt_at' => now(),
            ]);
    }

    protected function countReadyAgents(CallCenterQueues $queue): int
    {
        $queue->loadMissing('agents');

        return $queue->agents
            ->where('agent_status', 'Available')
            ->count();
    }

    protected function createAttempt(
        DialerCampaign $campaign,
        DialerCampaignLead $campaignLead,
        ?string $extensionUuid,
        string $bridgeDestination
    ): DialerAttempt {
        return DialerAttempt::query()->create([
            'campaign_uuid' => $campaign->uuid,
            'lead_uuid' => $campaignLead->lead_uuid,
            'extension_uuid' => $extensionUuid,
            'call_center_queue_uuid' => $campaign->call_center_queue_uuid,
            'mode' => $campaign->mode,
            'destination_number' => $campaignLead->lead->phone_number,
            'bridge_destination' => $bridgeDestination,
            'disposition' => 'queued',
        ]);
    }

    protected function resolveDisposition(string $domainUuid, array $payload): ?DialerDisposition
    {
        $uuid = $payload['disposition_uuid'] ?? null;
        $code = $payload['disposition_code'] ?? $payload['disposition'] ?? null;

        if (! $uuid && ! $code) {
            return null;
        }

        return DialerDisposition::query()
            ->when($uuid, fn($query) => $query->where('uuid', $uuid))
            ->when($code, fn($query) => $query->where('code', $code))
            ->where(function ($query) use ($domainUuid) {
                $query->whereNull('domain_uuid')
                    ->orWhere('domain_uuid', $domainUuid);
            })
            ->orderByRaw('CASE WHEN domain_uuid IS NULL THEN 1 ELSE 0 END')
            ->first();
    }

    protected function createCallbackFromDisposition(
        DialerAttempt $attempt,
        DialerLead $lead,
        ?CarbonImmutable $callbackAt,
        array $payload
    ): void {
        CallCenterCallback::query()->create([
            'domain_uuid' => $lead->domain_uuid,
            'call_center_queue_uuid' => $attempt->call_center_queue_uuid,
            'dialer_lead_uuid' => $lead->uuid,
            'contact_name' => trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: $lead->company,
            'phone_number' => $lead->phone_number,
            'state_code' => $lead->state_code,
            'timezone' => $lead->timezone,
            'status' => 'pending',
            'requested_at' => now(),
            'preferred_callback_at' => $callbackAt,
            'notes' => $payload['notes'] ?? null,
            'payload' => $payload['metadata'] ?? null,
        ]);
    }

    protected function resolveStateRule(DialerCampaign $campaign, DialerLead $lead): ?DialerStateRule
    {
        $stateCode = strtoupper($lead->state_code ?: $campaign->default_state_code ?: '');

        return $stateCode
            ? DialerStateRule::query()->where('state_code', $stateCode)->first()
            : null;
    }

    protected function resolveComplianceProfile(DialerCampaign $campaign, DialerLead $lead): ?DialerComplianceProfile
    {
        if (! $campaign->dialer_compliance_profile_uuid) {
            return null;
        }

        $campaign->loadMissing('complianceProfile');
        $profile = $campaign->complianceProfile;

        if (! $profile || ! $profile->is_active) {
            return null;
        }

        $stateCode = strtoupper($lead->state_code ?: $campaign->default_state_code ?: '');
        $stateCodes = collect($profile->state_codes ?? [])
            ->filter()
            ->map(fn($code) => strtoupper((string) $code))
            ->values()
            ->all();

        if ($stateCode && ! empty($stateCodes) && ! in_array($stateCode, $stateCodes, true)) {
            return null;
        }

        return $profile;
    }

    protected function resolveComplianceSchedule(DialerCampaign $campaign, DialerLead $lead): array|string|null
    {
        return $this->resolveComplianceProfile($campaign, $lead)?->schedule
            ?: $this->resolveStateRule($campaign, $lead)?->schedule;
    }

    protected function resolveTimezone(DialerCampaign $campaign, DialerLead $lead): string
    {
        $profileTimezone = $this->resolveComplianceProfile($campaign, $lead)?->timezone;

        return $lead->timezone
            ?: $profileTimezone
            ?: $this->resolveStateRule($campaign, $lead)?->timezone
            ?: $campaign->default_timezone
            ?: config('app.timezone');
    }

    protected function blankToNull(mixed $value): mixed
    {
        return is_string($value) && trim($value) === ''
            ? null
            : $value;
    }

    protected function deferCampaignLead(DialerCampaignLead $campaignLead, CarbonInterface $nextCallableAt, ?string $reason = null): void
    {
        $campaignLead->update([
            'status' => 'retrying',
            'next_attempt_at' => $nextCallableAt,
            'last_error' => $reason,
        ]);

        DialerLead::query()
            ->whereKey($campaignLead->lead_uuid)
            ->update([
                'status' => 'queued',
                'next_attempt_at' => $nextCallableAt,
            ]);
    }

    protected function blockCampaignLead(DialerCampaignLead $campaignLead, string $reason): void
    {
        $campaignLead->update([
            'status' => 'blocked',
            'last_error' => $reason,
        ]);
    }

    protected function assertCallable(DialerCampaign $campaign, DialerCampaignLead $campaignLead): void
    {
        $evaluation = $this->evaluateCampaignLead($campaign, $campaignLead);

        if (! $evaluation['allowed']) {
            if ($evaluation['final']) {
                $this->blockCampaignLead($campaignLead, $evaluation['reason']);
            } elseif ($evaluation['next_callable_at']) {
                $this->deferCampaignLead($campaignLead, $evaluation['next_callable_at'], $evaluation['reason']);
            }

            throw new RuntimeException($evaluation['reason'] ?? 'Lead is not callable right now.');
        }
    }

    protected function resolveAttemptFromPayload(array $payload): ?DialerAttempt
    {
        $attemptUuid = data_get($payload, 'data.dialer_attempt_uuid', data_get($payload, 'dialer_attempt_uuid'));

        if ($attemptUuid) {
            return DialerAttempt::query()->with(['campaign', 'lead'])->find($attemptUuid);
        }

        $jobUuid = data_get($payload, 'data.job_uuid', data_get($payload, 'job_uuid'));

        if ($jobUuid) {
            return DialerAttempt::query()
                ->with(['campaign', 'lead'])
                ->where('freeswitch_job_uuid', $jobUuid)
                ->latest('created_at')
                ->first();
        }

        return null;
    }

    protected function inferDispositionFromPayload(DialerAttempt $attempt, array $payload): ?string
    {
        $amdResult = strtolower((string) data_get($payload, 'data.amd_result', data_get($payload, 'amd_result', '')));
        $hangupCause = strtoupper((string) data_get($payload, 'data.hangup_cause', data_get($payload, 'hangup_cause', '')));
        $talkSeconds = (int) data_get($payload, 'data.talk_seconds', data_get($payload, 'talk_seconds', $attempt->talk_seconds ?? 0));

        if (in_array($amdResult, ['machine', 'voicemail'], true)) {
            return $attempt->campaign?->voicemail_disposition_code ?: 'voicemail';
        }

        return match ($hangupCause) {
            'USER_BUSY' => 'busy',
            'NO_ANSWER', 'NO_USER_RESPONSE', 'ALLOTTED_TIMEOUT' => 'no-answer',
            'UNALLOCATED_NUMBER', 'NUMBER_CHANGED', 'NO_ROUTE_DESTINATION' => 'invalid-number',
            default => $talkSeconds > 0 ? 'contacted' : null,
        };
    }

    protected function markLeadAsDnc(DialerLead $lead, ?string $reason, string $source): void
    {
        $lead->update([
            'do_not_call' => true,
            'status' => 'blocked',
        ]);

        DialerDncEntry::query()->firstOrCreate(
            [
                'domain_uuid' => $lead->domain_uuid,
                'normalized_phone' => $this->normalizePhone($lead->phone_number),
            ],
            [
                'phone_number' => $lead->phone_number,
                'reason' => $reason,
                'source' => $source,
            ]
        );
    }

    protected function notifyCampaignChanged(DialerCampaign $campaign, string $event, array $payload = []): void
    {
        $message = array_merge([
            'event' => $event,
            'campaign_uuid' => $campaign->uuid,
            'domain_uuid' => $campaign->domain_uuid,
            'timestamp' => now()->toIso8601String(),
        ], $payload);

        broadcast(new DialerCampaignUpdated($campaign->domain_uuid, $message));
        DispatchDialerWebhookJob::dispatch($campaign->uuid, $message);

        try {
            activity()
                ->performedOn($campaign)
                ->withProperties($message)
                ->event($event)
                ->log('Dialer activity recorded');
        } catch (Throwable $error) {
            logger()->warning('Unable to write dialer activity log: ' . $error->getMessage());
        }
    }

    protected function normalizeImportRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $key = strtolower(trim((string) $key));
            $key = str_replace([' ', '-'], '_', $key);
            $normalized[$key] = is_string($value) ? trim($value) : $value;
        }

        return [
            'first_name' => $normalized['first_name'] ?? $normalized['name'] ?? null,
            'last_name' => $normalized['last_name'] ?? null,
            'company' => $normalized['company'] ?? null,
            'phone_number' => $normalized['phone_number'] ?? $normalized['phone'] ?? $normalized['mobile'] ?? null,
            'email' => $normalized['email'] ?? null,
            'state_code' => $normalized['state_code'] ?? $normalized['state'] ?? null,
            'timezone' => $normalized['timezone'] ?? null,
            'external_ref' => $normalized['external_ref'] ?? $normalized['external_id'] ?? null,
            'do_not_call' => filter_var($normalized['do_not_call'] ?? false, FILTER_VALIDATE_BOOL),
            'metadata' => $row,
        ];
    }

    protected function evaluation(bool $allowed, ?string $reason, ?CarbonInterface $nextCallableAt, bool $final = false): array
    {
        return [
            'allowed' => $allowed,
            'reason' => $reason,
            'next_callable_at' => $nextCallableAt,
            'final' => $final,
        ];
    }
}
