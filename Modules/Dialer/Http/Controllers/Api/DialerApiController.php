<?php

namespace Modules\Dialer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Dialer\Models\DialerCampaign;
use Modules\Dialer\Models\DialerLead;
use Modules\Dialer\Services\DialerService;
use Throwable;

class DialerApiController extends Controller
{
    public function __construct(
        protected DialerService $service
    ) {}

    public function campaigns(): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_view'), 403);

        return response()->json(
            DialerCampaign::query()
                ->where('domain_uuid', $this->domainUuid())
                ->latest()
                ->get()
        );
    }

    public function showCampaign(DialerCampaign $campaign): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_view'), 403);
        abort_unless($campaign->domain_uuid === $this->domainUuid(), 404);

        return response()->json($campaign->load(['queue', 'campaignLeads', 'attempts']));
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_manage'), 403);
        $validated = $this->validateCampaign($request);

        try {
            $campaign = $this->service->createCampaign($this->domainUuid(), $validated);
        } catch (Throwable $error) {
            report($error);

            return response()->json([
                'message' => __($error->getMessage() ?: 'Unable to create the campaign right now.'),
            ], 422);
        }

        return response()->json($campaign, 201);
    }

    public function updateCampaign(Request $request, DialerCampaign $campaign): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_manage'), 403);
        abort_unless($campaign->domain_uuid === $this->domainUuid(), 404);

        try {
            $campaign = $this->service->updateCampaign($campaign, $this->validateCampaign($request));
        } catch (Throwable $error) {
            report($error);

            return response()->json([
                'message' => __($error->getMessage() ?: 'Unable to update the campaign right now.'),
            ], 422);
        }

        return response()->json($campaign);
    }

    public function destroyCampaign(DialerCampaign $campaign): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_manage'), 403);
        abort_unless($campaign->domain_uuid === $this->domainUuid(), 404);

        $campaign->campaignLeads()->delete();
        $campaign->attempts()->delete();
        $campaign->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function leads(): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_view'), 403);
        return response()->json(
            DialerLead::query()
                ->where('domain_uuid', $this->domainUuid())
                ->latest()
                ->get()
        );
    }

    public function showLead(DialerLead $lead): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_view'), 403);
        abort_unless($lead->domain_uuid === $this->domainUuid(), 404);

        return response()->json($lead->load(['campaignLinks', 'attempts']));
    }

    public function storeLead(Request $request): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_manage'), 403);
        $validated = $this->validateLead($request);

        $lead = DialerLead::query()->create([
            'domain_uuid' => $this->domainUuid(),
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

        return response()->json($lead->fresh(), 201);
    }

    public function updateLead(Request $request, DialerLead $lead): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_manage'), 403);
        abort_unless($lead->domain_uuid === $this->domainUuid(), 404);
        $validated = $this->validateLead($request, false);

        $lead->update([
            'first_name' => $validated['first_name'] ?? $lead->first_name,
            'last_name' => $validated['last_name'] ?? $lead->last_name,
            'company' => $validated['company'] ?? $lead->company,
            'phone_number' => $validated['phone_number'] ?? $lead->phone_number,
            'email' => $validated['email'] ?? $lead->email,
            'state_code' => isset($validated['state_code']) ? strtoupper($validated['state_code']) : $lead->state_code,
            'timezone' => $validated['timezone'] ?? $lead->timezone,
            'do_not_call' => (bool) ($validated['do_not_call'] ?? $lead->do_not_call),
        ]);

        return response()->json($lead->fresh());
    }

    public function destroyLead(DialerLead $lead): JsonResponse
    {
        abort_unless(userCheckPermission('dialer_manage'), 403);
        abort_unless($lead->domain_uuid === $this->domainUuid(), 404);
        $lead->campaignLinks()->delete();
        $lead->delete();

        return response()->json(['status' => 'deleted']);
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
            'call_center_queue_uuid' => ['nullable', 'uuid'],
            'dialer_compliance_profile_uuid' => ['nullable', 'uuid'],
            'pacing_ratio' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'max_inflight_calls' => ['nullable', 'integer', 'min:1', 'max:1000'],
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
            'busy_disposition_code' => ['nullable', 'string', 'max:50'],
            'no_answer_disposition_code' => ['nullable', 'string', 'max:50'],
            'invalid_number_disposition_code' => ['nullable', 'string', 'max:50'],
            'voicemail_action' => ['nullable', Rule::in(['hangup', 'continue'])],
        ]);

        $validated['default_state_code'] = isset($validated['default_state_code']) ? strtoupper($validated['default_state_code']) : null;
        $validated['respect_dnc'] = (bool) ($validated['respect_dnc'] ?? true);
        $validated['amd_enabled'] = (bool) ($validated['amd_enabled'] ?? false);

        return $validated;
    }

    protected function validateLead(Request $request, bool $requireCampaigns = true): array
    {
        $rules = [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'state_code' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'do_not_call' => ['nullable', 'boolean'],
        ];

        if ($requireCampaigns) {
            $rules['campaign_uuids'] = ['required', 'array', 'min:1'];
            $rules['campaign_uuids.*'] = ['uuid'];
        }

        return $request->validate($rules);
    }

    protected function domainUuid(): string
    {
        return auth()->user()?->domain_uuid ?? session('domain_uuid');
    }
}
