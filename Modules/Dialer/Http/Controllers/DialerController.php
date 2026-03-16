<?php

namespace Modules\Dialer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CallCenterQueues;
use App\Models\Extensions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Dialer\Jobs\ImportDialerLeadsJob;
use Modules\Dialer\Models\DialerCampaign;
use Modules\Dialer\Models\DialerCampaignLead;
use Modules\Dialer\Models\DialerDisposition;
use Modules\Dialer\Models\DialerDncEntry;
use Modules\Dialer\Models\DialerImportBatch;
use Modules\Dialer\Models\DialerLead;
use Modules\Dialer\Models\DialerStateRule;
use Modules\Dialer\Services\DialerComplianceService;
use Modules\Dialer\Services\DialerService;
use Throwable;

class DialerController extends Controller
{
    public function __construct(
        protected DialerService $service,
        protected DialerComplianceService $compliance
    ) {}

    public function index()
    {
        if (! userCheckPermission('dialer_view')) {
            return redirect('/');
        }

        $campaigns = DialerCampaign::query()
            ->with('queue')
            ->withCount([
                'campaignLeads as queued_leads_count' => fn($query) => $query->where('status', 'queued'),
                'campaignLeads as calling_leads_count' => fn($query) => $query->where('status', 'calling'),
            ])
            ->where('domain_uuid', session('domain_uuid'))
            ->latest()
            ->get()
            ->map(fn(DialerCampaign $campaign) => [
                'uuid' => $campaign->uuid,
                'name' => $campaign->name,
                'description' => $campaign->description,
                'mode' => $campaign->mode,
                'status' => $campaign->status,
                'caller_id_name' => $campaign->caller_id_name,
                'caller_id_number' => $campaign->caller_id_number,
                'outbound_prefix' => $campaign->outbound_prefix,
                'call_center_queue_uuid' => $campaign->call_center_queue_uuid,
                'queue_label' => $campaign->queue ? ($campaign->queue->queue_extension . ' - ' . $campaign->queue->queue_name) : null,
                'pacing_ratio' => (float) $campaign->pacing_ratio,
                'preview_seconds' => $campaign->preview_seconds,
                'originate_timeout' => $campaign->originate_timeout,
                'max_attempts' => $campaign->max_attempts,
                'default_state_code' => $campaign->default_state_code,
                'default_timezone' => $campaign->default_timezone,
                'retry_backoff_minutes' => $campaign->retry_backoff_minutes,
                'daily_retry_limit' => $campaign->daily_retry_limit,
                'respect_dnc' => (bool) $campaign->respect_dnc,
                'amd_enabled' => (bool) $campaign->amd_enabled,
                'amd_strategy' => $campaign->amd_strategy,
                'webhook_url' => $campaign->webhook_url,
                'callback_disposition_code' => $campaign->callback_disposition_code,
                'voicemail_disposition_code' => $campaign->voicemail_disposition_code,
                'queued_leads_count' => $campaign->queued_leads_count,
                'calling_leads_count' => $campaign->calling_leads_count,
            ])
            ->values();

        $leads = DialerLead::query()
            ->where('domain_uuid', session('domain_uuid'))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn(DialerLead $lead) => [
                'uuid' => $lead->uuid,
                'name' => trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: $lead->company ?: $lead->phone_number,
                'phone_number' => $lead->phone_number,
                'company' => $lead->company,
                'status' => $lead->status,
                'email' => $lead->email,
                'state_code' => $lead->state_code,
                'timezone' => $lead->timezone,
                'do_not_call' => (bool) $lead->do_not_call,
                'last_disposition' => $lead->last_disposition,
                'next_attempt_at' => $lead->next_attempt_at,
            ])
            ->values();

        $attempts = \Modules\Dialer\Models\DialerAttempt::query()
            ->with(['campaign', 'lead', 'dispositionModel'])
            ->whereHas('campaign', fn($query) => $query->where('domain_uuid', session('domain_uuid')))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($attempt) => [
                'uuid' => $attempt->uuid,
                'campaign_uuid' => $attempt->campaign_uuid,
                'campaign_name' => $attempt->campaign?->name,
                'lead_uuid' => $attempt->lead_uuid,
                'lead_name' => trim(($attempt->lead?->first_name ?? '') . ' ' . ($attempt->lead?->last_name ?? '')) ?: $attempt->lead?->company ?: $attempt->destination_number,
                'destination_number' => $attempt->destination_number,
                'mode' => $attempt->mode,
                'disposition' => $attempt->disposition,
                'amd_result' => $attempt->amd_result,
                'hangup_cause' => $attempt->hangup_cause,
                'talk_seconds' => $attempt->talk_seconds,
                'wait_seconds' => $attempt->wait_seconds,
                'completed_at' => $attempt->completed_at,
            ])
            ->values();

        $dispositions = DialerDisposition::query()
            ->where(function ($query) {
                $query->whereNull('domain_uuid')
                    ->orWhere('domain_uuid', session('domain_uuid'));
            })
            ->orderBy('name')
            ->get()
            ->map(fn(DialerDisposition $disposition) => [
                'uuid' => $disposition->uuid,
                'name' => $disposition->name,
                'code' => $disposition->code,
                'is_final' => (bool) $disposition->is_final,
                'is_callback' => (bool) $disposition->is_callback,
                'mark_dnc' => (bool) $disposition->mark_dnc,
                'default_callback_offset_minutes' => $disposition->default_callback_offset_minutes,
                'description' => $disposition->description,
            ])
            ->values();

        $dncEntries = DialerDncEntry::query()
            ->where(function ($query) {
                $query->whereNull('domain_uuid')
                    ->orWhere('domain_uuid', session('domain_uuid'));
            })
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn(DialerDncEntry $entry) => [
                'uuid' => $entry->uuid,
                'phone_number' => $entry->phone_number,
                'reason' => $entry->reason,
                'source' => $entry->source,
                'expires_at' => $entry->expires_at,
            ])
            ->values();

        $stateRules = DialerStateRule::query()
            ->orderBy('state_code')
            ->get()
            ->map(fn(DialerStateRule $rule) => [
                'uuid' => $rule->uuid,
                'state_code' => $rule->state_code,
                'state_name' => $rule->state_name,
                'timezone' => $rule->timezone,
                'schedule' => $rule->schedule,
                'schedule_summary' => $this->compliance->summarize($rule->schedule),
                'notes' => $rule->notes,
                'legal_reference_url' => $rule->legal_reference_url,
            ])
            ->values();

        $importBatches = DialerImportBatch::query()
            ->where('domain_uuid', session('domain_uuid'))
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn(DialerImportBatch $batch) => [
                'uuid' => $batch->uuid,
                'file_name' => $batch->file_name,
                'status' => $batch->status,
                'total_rows' => $batch->total_rows,
                'imported_rows' => $batch->imported_rows,
                'error_rows' => $batch->error_rows,
                'completed_at' => $batch->completed_at,
                'errors' => $batch->errors,
            ])
            ->values();

        return Inertia::render('Index::Dialer', [
            'campaigns' => $campaigns,
            'leads' => $leads,
            'attempts' => $attempts,
            'dispositions' => $dispositions,
            'dncEntries' => $dncEntries,
            'stateRules' => $stateRules,
            'importBatches' => $importBatches,
            'options' => [
                'queues' => CallCenterQueues::query()
                    ->where('domain_uuid', session('domain_uuid'))
                    ->orderBy('queue_extension')
                    ->get()
                    ->map(fn(CallCenterQueues $queue) => [
                        'value' => $queue->call_center_queue_uuid,
                        'label' => $queue->queue_extension . ' - ' . $queue->queue_name,
                    ])
                    ->values(),
                'extensions' => Extensions::query()
                    ->where('domain_uuid', session('domain_uuid'))
                    ->orderBy('extension')
                    ->get(['extension_uuid', 'extension', 'effective_caller_id_name', 'user_context'])
                    ->map(fn(Extensions $extension) => [
                        'value' => $extension->extension_uuid,
                        'label' => $extension->extension . ' - ' . ($extension->effective_caller_id_name ?: $extension->extension),
                    ])
                    ->values(),
                'modes' => [
                    ['value' => 'manual', 'label' => 'Manual'],
                    ['value' => 'preview', 'label' => 'Preview'],
                    ['value' => 'progressive', 'label' => 'Progressive'],
                    ['value' => 'power', 'label' => 'Power/Predictive'],
                ],
                'statuses' => [
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'paused', 'label' => 'Paused'],
                    ['value' => 'completed', 'label' => 'Completed'],
                ],
                'states' => DialerStateRule::query()
                    ->orderBy('state_code')
                    ->get(['state_code', 'state_name', 'timezone'])
                    ->map(fn(DialerStateRule $rule) => [
                        'value' => $rule->state_code,
                        'label' => $rule->state_code . ' - ' . $rule->state_name,
                        'timezone' => $rule->timezone,
                    ])
                    ->values(),
            ],
            'routes' => [
                'storeCampaign' => route('dialer.campaigns.store'),
                'storeLead' => route('dialer.leads.store'),
                'storeDisposition' => route('dialer.dispositions.store'),
                'storeDnc' => route('dialer.dnc.store'),
                'storeStateRule' => route('dialer.state-rules.store'),
                'importLeads' => route('dialer.imports.store'),
            ],
        ]);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $this->validateCampaign($request);
        $validated['domain_uuid'] = session('domain_uuid');

        DialerCampaign::query()->create($validated);

        return response()->json(['messages' => ['success' => [__('Campaign created successfully.')]]]);
    }

    public function updateCampaign(Request $request, DialerCampaign $campaign): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($campaign->domain_uuid === session('domain_uuid'), 404);

        $campaign->update($this->validateCampaign($request));

        return response()->json(['messages' => ['success' => [__('Campaign updated successfully.')]]]);
    }

    public function destroyCampaign(DialerCampaign $campaign): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($campaign->domain_uuid === session('domain_uuid'), 404);

        $campaign->campaignLeads()->delete();
        $campaign->attempts()->delete();
        $campaign->delete();

        return response()->json(['messages' => ['success' => [__('Campaign deleted successfully.')]]]);
    }

    public function storeLead(Request $request): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'state_code' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'do_not_call' => ['nullable', 'boolean'],
            'campaign_uuids' => ['required', 'array', 'min:1'],
            'campaign_uuids.*' => ['uuid'],
        ]);

        $lead = DialerLead::query()->create([
            'domain_uuid' => session('domain_uuid'),
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'company' => $validated['company'] ?? null,
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'state_code' => isset($validated['state_code']) ? strtoupper($validated['state_code']) : null,
            'timezone' => $validated['timezone'] ?? null,
            'do_not_call' => (bool) ($validated['do_not_call'] ?? false),
            'status' => ! empty($validated['do_not_call']) ? 'blocked' : 'new',
        ]);

        $this->service->createCampaignLeadAssignments($lead, $validated['campaign_uuids']);

        if ($lead->do_not_call) {
            DialerDncEntry::query()->firstOrCreate(
                [
                    'domain_uuid' => session('domain_uuid'),
                    'normalized_phone' => $this->service->normalizePhone($lead->phone_number),
                ],
                [
                    'phone_number' => $lead->phone_number,
                    'reason' => 'Lead created with do-not-call flag',
                    'source' => 'manual',
                ]
            );
        }

        return response()->json(['messages' => ['success' => [__('Lead created successfully.')]]]);
    }

    public function destroyLead(DialerLead $lead): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($lead->domain_uuid === session('domain_uuid'), 404);

        $lead->campaignLinks()->delete();
        $lead->delete();

        return response()->json(['messages' => ['success' => [__('Lead deleted successfully.')]]]);
    }

    public function storeDisposition(Request $request): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'is_final' => ['nullable', 'boolean'],
            'is_callback' => ['nullable', 'boolean'],
            'mark_dnc' => ['nullable', 'boolean'],
            'auto_close_lead' => ['nullable', 'boolean'],
            'default_callback_offset_minutes' => ['nullable', 'integer', 'min:1', 'max:43200'],
            'description' => ['nullable', 'string'],
        ]);

        DialerDisposition::query()->updateOrCreate(
            [
                'domain_uuid' => session('domain_uuid'),
                'code' => $validated['code'],
            ],
            [
                'name' => $validated['name'],
                'is_final' => (bool) ($validated['is_final'] ?? false),
                'is_callback' => (bool) ($validated['is_callback'] ?? false),
                'mark_dnc' => (bool) ($validated['mark_dnc'] ?? false),
                'auto_close_lead' => (bool) ($validated['auto_close_lead'] ?? false),
                'default_callback_offset_minutes' => $validated['default_callback_offset_minutes'] ?? null,
                'description' => $validated['description'] ?? null,
            ]
        );

        return response()->json(['messages' => ['success' => [__('Disposition saved successfully.')]]]);
    }

    public function dispositionAttempt(Request $request, \Modules\Dialer\Models\DialerAttempt $attempt): JsonResponse
    {
        if (! userCheckPermission('dialer_manage') && ! userCheckPermission('dialer_run')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $attempt->loadMissing('campaign');
        abort_unless($attempt->campaign?->domain_uuid === session('domain_uuid'), 404);

        $validated = $request->validate([
            'disposition_uuid' => ['nullable', 'uuid'],
            'disposition_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'preferred_callback_at' => ['nullable', 'date'],
        ]);

        try {
            $this->service->recordDisposition($attempt, $validated);
        } catch (Throwable $error) {
            return response()->json(['messages' => ['error' => [__($error->getMessage())]]], 422);
        }

        return response()->json(['messages' => ['success' => [__('Attempt disposition saved successfully.')]]]);
    }

    public function storeDncEntry(Request $request): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
        ]);

        DialerDncEntry::query()->updateOrCreate(
            [
                'domain_uuid' => session('domain_uuid'),
                'normalized_phone' => $this->service->normalizePhone($validated['phone_number']),
            ],
            [
                'phone_number' => $validated['phone_number'],
                'reason' => $validated['reason'] ?? null,
                'source' => $validated['source'] ?? 'manual',
                'expires_at' => $validated['expires_at'] ?? null,
            ]
        );

        return response()->json(['messages' => ['success' => [__('Do-not-call entry saved successfully.')]]]);
    }

    public function destroyDncEntry(DialerDncEntry $entry): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($entry->domain_uuid === null || $entry->domain_uuid === session('domain_uuid'), 404);
        $entry->delete();

        return response()->json(['messages' => ['success' => [__('Do-not-call entry deleted successfully.')]]]);
    }

    public function storeStateRule(Request $request): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $this->validateStateRule($request);

        DialerStateRule::query()->updateOrCreate(
            ['state_code' => strtoupper($validated['state_code'])],
            [
                'state_name' => $validated['state_name'],
                'timezone' => $validated['timezone'],
                'schedule' => $this->compliance->normalizeSchedule($validated['schedule']),
                'notes' => $validated['notes'] ?? null,
                'legal_reference_url' => $validated['legal_reference_url'] ?? null,
            ]
        );

        return response()->json(['messages' => ['success' => [__('State dialing rule saved successfully.')]]]);
    }

    public function updateStateRule(Request $request, DialerStateRule $stateRule): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $this->validateStateRule($request);

        $stateRule->update([
            'state_name' => $validated['state_name'],
            'timezone' => $validated['timezone'],
            'schedule' => $this->compliance->normalizeSchedule($validated['schedule']),
            'notes' => $validated['notes'] ?? null,
            'legal_reference_url' => $validated['legal_reference_url'] ?? null,
        ]);

        return response()->json(['messages' => ['success' => [__('State dialing rule saved successfully.')]]]);
    }

    public function importLeads(Request $request): JsonResponse
    {
        if (! userCheckPermission('dialer_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xls,xlsx'],
            'campaign_uuids' => ['required', 'array', 'min:1'],
            'campaign_uuids.*' => ['uuid'],
            'default_state_code' => ['nullable', 'string', 'size:2'],
            'default_timezone' => ['nullable', 'string', 'max:100'],
        ]);

        $file = $validated['file'];
        $storedPath = $file->storeAs(
            'dialer-imports',
            uniqid('dialer-import-', true) . '.' . $file->getClientOriginalExtension(),
            'local'
        );

        $batch = DialerImportBatch::query()->create([
            'domain_uuid' => session('domain_uuid'),
            'campaign_uuid' => $validated['campaign_uuids'][0] ?? null,
            'user_uuid' => auth()->user()?->user_uuid,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'settings' => [
                'campaign_uuids' => $validated['campaign_uuids'],
                'default_state_code' => isset($validated['default_state_code']) ? strtoupper($validated['default_state_code']) : null,
                'default_timezone' => $validated['default_timezone'] ?? null,
            ],
        ]);

        ImportDialerLeadsJob::dispatch($batch->uuid);

        return response()->json([
            'messages' => ['success' => [__('Lead import queued successfully.')]],
            'batch_uuid' => $batch->uuid,
        ]);
    }

    public function previewNextLead(DialerCampaign $campaign): JsonResponse
    {
        if (! userCheckPermission('dialer_view')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($campaign->domain_uuid === session('domain_uuid'), 404);

        $campaignLead = $this->service->nextLead($campaign);

        if (! $campaignLead) {
            return response()->json(['lead' => null]);
        }

        return response()->json([
            'lead' => [
                'uuid' => $campaignLead->lead->uuid,
                'name' => trim(($campaignLead->lead->first_name ?? '') . ' ' . ($campaignLead->lead->last_name ?? '')) ?: $campaignLead->lead->company ?: $campaignLead->lead->phone_number,
                'phone_number' => $campaignLead->lead->phone_number,
                'company' => $campaignLead->lead->company,
                'email' => $campaignLead->lead->email,
            ],
        ]);
    }

    public function dialLead(Request $request, DialerCampaign $campaign): JsonResponse
    {
        if (! userCheckPermission('dialer_run')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($campaign->domain_uuid === session('domain_uuid'), 404);

        $validated = $request->validate([
            'lead_uuid' => ['required', 'uuid'],
            'extension_uuid' => ['required', 'uuid'],
        ]);

        $campaignLead = DialerCampaignLead::query()
            ->with('lead')
            ->where('campaign_uuid', $campaign->uuid)
            ->where('lead_uuid', $validated['lead_uuid'])
            ->firstOrFail();

        $extension = Extensions::query()
            ->where('domain_uuid', session('domain_uuid'))
            ->whereKey($validated['extension_uuid'])
            ->firstOrFail();

        try {
            $result = $this->service->startManualCall($campaign, $campaignLead, $extension);
        } catch (Throwable $error) {
            return response()->json([
                'messages' => ['error' => [__($error->getMessage())]],
            ], 422);
        }

        return response()->json([
            'messages' => ['success' => [__('Call dispatched to FreeSWITCH.')]],
            'job_uuid' => $result['job_uuid'],
        ]);
    }

    public function runCampaign(DialerCampaign $campaign): JsonResponse
    {
        if (! userCheckPermission('dialer_run')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($campaign->domain_uuid === session('domain_uuid'), 404);

        try {
            $result = $this->service->processCampaign($campaign);
        } catch (Throwable $error) {
            return response()->json([
                'messages' => ['error' => [__($error->getMessage())]],
            ], 422);
        }

        return response()->json([
            'messages' => ['success' => [__('Campaign cycle processed. :count call(s) dispatched.', ['count' => $result['dialed']])]],
            'dialed' => $result['dialed'],
        ]);
    }

    protected function validateCampaign(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mode' => ['required', Rule::in(['manual', 'preview', 'progressive', 'power'])],
            'status' => ['required', Rule::in(['draft', 'active', 'paused', 'completed'])],
            'caller_id_name' => ['nullable', 'string', 'max:255'],
            'caller_id_number' => ['nullable', 'string', 'max:50'],
            'outbound_prefix' => ['nullable', 'string', 'max:20'],
            'default_state_code' => ['nullable', 'string', 'size:2'],
            'default_timezone' => ['nullable', 'string', 'max:100'],
            'call_center_queue_uuid' => ['nullable', 'uuid', 'required_if:mode,progressive,power'],
            'pacing_ratio' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'preview_seconds' => ['nullable', 'integer', 'min:5', 'max:3600'],
            'originate_timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:25'],
            'retry_backoff_minutes' => ['nullable', 'integer', 'min:1', 'max:43200'],
            'daily_retry_limit' => ['nullable', 'integer', 'min:1', 'max:25'],
            'respect_dnc' => ['nullable', 'boolean'],
            'amd_enabled' => ['nullable', 'boolean'],
            'amd_strategy' => ['nullable', 'string', 'max:50'],
            'webhook_url' => ['nullable', 'url', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'callback_disposition_code' => ['nullable', 'string', 'max:50'],
            'voicemail_disposition_code' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['default_state_code'] = isset($validated['default_state_code'])
            ? strtoupper($validated['default_state_code'])
            : null;
        $validated['respect_dnc'] = (bool) ($validated['respect_dnc'] ?? true);
        $validated['amd_enabled'] = (bool) ($validated['amd_enabled'] ?? false);

        return $validated;
    }

    protected function validateStateRule(Request $request): array
    {
        return $request->validate([
            'state_code' => ['required', 'string', 'size:2'],
            'state_name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:100'],
            'schedule' => ['required', 'array'],
            'schedule.monday.enabled' => ['required', 'boolean'],
            'schedule.monday.start' => ['nullable', 'date_format:H:i'],
            'schedule.monday.end' => ['nullable', 'date_format:H:i'],
            'schedule.tuesday.enabled' => ['required', 'boolean'],
            'schedule.tuesday.start' => ['nullable', 'date_format:H:i'],
            'schedule.tuesday.end' => ['nullable', 'date_format:H:i'],
            'schedule.wednesday.enabled' => ['required', 'boolean'],
            'schedule.wednesday.start' => ['nullable', 'date_format:H:i'],
            'schedule.wednesday.end' => ['nullable', 'date_format:H:i'],
            'schedule.thursday.enabled' => ['required', 'boolean'],
            'schedule.thursday.start' => ['nullable', 'date_format:H:i'],
            'schedule.thursday.end' => ['nullable', 'date_format:H:i'],
            'schedule.friday.enabled' => ['required', 'boolean'],
            'schedule.friday.start' => ['nullable', 'date_format:H:i'],
            'schedule.friday.end' => ['nullable', 'date_format:H:i'],
            'schedule.saturday.enabled' => ['required', 'boolean'],
            'schedule.saturday.start' => ['nullable', 'date_format:H:i'],
            'schedule.saturday.end' => ['nullable', 'date_format:H:i'],
            'schedule.sunday.enabled' => ['required', 'boolean'],
            'schedule.sunday.start' => ['nullable', 'date_format:H:i'],
            'schedule.sunday.end' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
            'legal_reference_url' => ['nullable', 'url', 'max:255'],
        ]);
    }
}
