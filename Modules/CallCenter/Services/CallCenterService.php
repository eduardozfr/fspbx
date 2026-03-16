<?php

namespace Modules\CallCenter\Services;

use App\Models\CallCenterAgents;
use App\Models\CallCenterQueueAgents;
use App\Models\CallCenterQueues;
use App\Models\Extensions;
use App\Models\Groups;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\FreeswitchEslService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\CallCenter\Events\CallCenterWallboardUpdated;
use Modules\CallCenter\Models\CallCenterAgentPause;
use Modules\CallCenter\Models\CallCenterCallback;
use Modules\CallCenter\Models\CallCenterEvent;
use Modules\CallCenter\Models\CallCenterMonitoringSession;
use Modules\CallCenter\Models\CallCenterPauseReason;
use Throwable;

class CallCenterService
{
    public const GLOBAL_DOMAIN_UUID = '00000000-0000-0000-0000-000000000000';

    public function getOverview(string $domainUuid, string $domainName): array
    {
        $queues = CallCenterQueues::query()
            ->with('agents')
            ->where('domain_uuid', $domainUuid)
            ->orderBy('queue_extension')
            ->get();

        $agents = CallCenterAgents::query()
            ->with(['extension', 'queues'])
            ->where('domain_uuid', $domainUuid)
            ->orderBy('agent_name')
            ->get();

        [$registeredExtensions, $busyExtensions, $channels] = $this->getLiveRuntimeState();
        $openPauses = CallCenterAgentPause::query()
            ->with('reason')
            ->where('domain_uuid', $domainUuid)
            ->whereNull('ended_at')
            ->get()
            ->keyBy('call_center_agent_uuid');

        $queueRows = $queues->map(function (CallCenterQueues $queue) use ($domainName) {
            $wallboard = $this->queueWallboardRow($queue, $domainName);

            return [
                'uuid' => $queue->call_center_queue_uuid,
                'name' => $queue->queue_name,
                'extension' => $queue->queue_extension,
                'strategy' => $queue->queue_strategy,
                'max_wait_time' => (int) ($queue->queue_max_wait_time ?? 0),
                'description' => $queue->queue_description,
                'agents' => $queue->agents->map(fn(CallCenterAgents $agent) => [
                    'uuid' => $agent->call_center_agent_uuid,
                    'name' => $agent->agent_name,
                    'extension' => $agent->agent_id,
                ])->values(),
                'live_waiting_members' => $wallboard['live_waiting_members'],
                'service_level_percent' => $wallboard['service_level_percent'],
                'abandonment_rate' => $wallboard['abandonment_rate'],
                'avg_wait_seconds' => $wallboard['avg_wait_seconds'],
            ];
        })->values();

        $agentRows = $agents->map(function (CallCenterAgents $agent) use ($registeredExtensions, $busyExtensions, $openPauses) {
            $extension = (string) $agent->agent_id;
            $isBusy = $busyExtensions->contains($extension);
            $isOnline = $registeredExtensions->contains($extension);
            $pause = $openPauses->get($agent->call_center_agent_uuid);

            return [
                'uuid' => $agent->call_center_agent_uuid,
                'name' => $agent->agent_name,
                'extension' => $agent->agent_id,
                'status' => $agent->agent_status,
                'live_status' => $isBusy ? 'Busy' : ($isOnline ? 'Online' : 'Offline'),
                'pause_reason' => $pause?->reason?->name,
                'paused_at' => $pause?->started_at,
                'call_timeout' => (int) ($agent->agent_call_timeout ?? 20),
                'wrap_up_time' => (int) ($agent->agent_wrap_up_time ?? 10),
                'reject_delay_time' => (int) ($agent->agent_reject_delay_time ?? 10),
                'queues' => $agent->queues->map(fn(CallCenterQueues $queue) => [
                    'uuid' => $queue->call_center_queue_uuid,
                    'name' => $queue->queue_name,
                    'extension' => $queue->queue_extension,
                ])->values(),
            ];
        })->values();

        $wallboard = $this->getWallboardData($domainUuid, $domainName, $queues, $agents, $channels);
        $callbacks = $this->getCallbacks($domainUuid);

        return [
            'summary' => [
                'queues' => $queues->count(),
                'agents' => $agents->count(),
                'online_agents' => $agentRows->where('live_status', 'Online')->count(),
                'busy_agents' => $agentRows->where('live_status', 'Busy')->count(),
                'offline_agents' => $agentRows->where('live_status', 'Offline')->count(),
                'paused_agents' => $openPauses->count(),
                'pending_callbacks' => $callbacks->where('status', 'pending')->count(),
            ],
            'queues' => $queueRows,
            'agents' => $agentRows,
            'wallboard' => $wallboard,
            'callbacks' => $callbacks,
            'pause_reasons' => $this->getPauseReasons($domainUuid),
            'options' => [
                'agents' => $this->getAgentOptions($domainUuid),
                'extensions' => $this->getExtensionOptions($domainUuid),
                'strategies' => [
                    ['value' => 'ring-all', 'label' => 'Ring all'],
                    ['value' => 'longest-idle-agent', 'label' => 'Longest idle agent'],
                    ['value' => 'round-robin', 'label' => 'Round robin'],
                    ['value' => 'top-down', 'label' => 'Top down'],
                ],
                'statuses' => [
                    ['value' => 'Available', 'label' => 'Available'],
                    ['value' => 'Logged Out', 'label' => 'Logged out'],
                    ['value' => 'On Break', 'label' => 'On break'],
                ],
                'monitoring_modes' => [
                    ['value' => 'listen', 'label' => 'Listen'],
                    ['value' => 'whisper', 'label' => 'Whisper'],
                    ['value' => 'barge', 'label' => 'Barge'],
                ],
                'domain_name' => $domainName,
            ],
        ];
    }

    public function syncQueueAgents(CallCenterQueues $queue, array $agentUuids): void
    {
        if (empty($agentUuids)) {
            CallCenterQueueAgents::query()
                ->where('call_center_queue_uuid', $queue->call_center_queue_uuid)
                ->delete();

            return;
        }

        CallCenterQueueAgents::query()
            ->where('call_center_queue_uuid', $queue->call_center_queue_uuid)
            ->whereNotIn('call_center_agent_uuid', $agentUuids)
            ->delete();

        foreach (array_values($agentUuids) as $index => $agentUuid) {
            $agent = CallCenterAgents::query()
                ->where('domain_uuid', $queue->domain_uuid)
                ->whereKey($agentUuid)
                ->first();

            if (! $agent) {
                continue;
            }

            CallCenterQueueAgents::query()->updateOrCreate(
                [
                    'call_center_queue_uuid' => $queue->call_center_queue_uuid,
                    'call_center_agent_uuid' => $agent->call_center_agent_uuid,
                ],
                [
                    'domain_uuid' => $queue->domain_uuid,
                    'agent_name' => $agent->agent_name,
                    'queue_name' => $queue->queue_name,
                    'tier_level' => 1,
                    'tier_position' => $index + 1,
                ]
            );
        }

        $this->notifyWallboard($queue->domain_uuid, ['event' => 'call-center.queue.updated']);
    }

    public function createOrUpdateAgent(string $domainUuid, string $domainName, array $validated, ?CallCenterAgents $agent = null): CallCenterAgents
    {
        $extension = Extensions::query()
            ->with('voicemail')
            ->where('domain_uuid', $domainUuid)
            ->whereKey($validated['extension_uuid'])
            ->firstOrFail();

        $user = User::query()
            ->where('domain_uuid', $domainUuid)
            ->where('extension_uuid', $extension->extension_uuid)
            ->first();

        $payload = [
            'domain_uuid' => $domainUuid,
            'user_uuid' => $user?->user_uuid,
            'agent_name' => $validated['agent_name'] ?: ($extension->extension . '@' . $domainName),
            'agent_type' => 'callback',
            'agent_call_timeout' => (int) ($validated['agent_call_timeout'] ?? 20),
            'agent_id' => $extension->extension,
            'agent_password' => null,
            'agent_contact' => '[leg_timeout=' . (int) ($validated['agent_call_timeout'] ?? 20) . "]user/{$extension->extension}@{$domainName}",
            'agent_status' => $validated['agent_status'] ?? 'Available',
            'agent_logout' => 'false',
            'agent_max_no_answer' => 3,
            'agent_wrap_up_time' => (int) ($validated['agent_wrap_up_time'] ?? 10),
            'agent_reject_delay_time' => (int) ($validated['agent_reject_delay_time'] ?? 10),
            'agent_busy_delay_time' => 10,
            'agent_no_answer_delay_time' => 15,
            'agent_record' => 'false',
        ];

        if ($agent) {
            $agent->fill($payload)->save();
            $this->notifyWallboard($domainUuid, ['event' => 'call-center.agent.updated', 'agent_uuid' => $agent->call_center_agent_uuid]);

            return $agent->fresh(['extension', 'queues']);
        }

        $created = CallCenterAgents::query()->create($payload)->fresh(['extension', 'queues']);
        $this->notifyWallboard($domainUuid, ['event' => 'call-center.agent.created', 'agent_uuid' => $created->call_center_agent_uuid]);

        return $created;
    }

    public function provisionUserFromExtension(string $domainUuid, string $domainName, string $extensionUuid, string $role): array
    {
        $extension = Extensions::query()
            ->with('voicemail')
            ->where('domain_uuid', $domainUuid)
            ->whereKey($extensionUuid)
            ->firstOrFail();

        return DB::transaction(function () use ($extension, $domainUuid, $domainName, $role) {
            $user = User::query()
                ->where('domain_uuid', $domainUuid)
                ->where('extension_uuid', $extension->extension_uuid)
                ->first();

            if (! $user) {
                $firstName = $extension->directory_first_name ?: 'Agent';
                $lastName = $extension->directory_last_name ?: $extension->extension;
                $email = $extension->email ?: "contact-center-{$extension->extension}@{$domainName}";

                $user = User::query()->create([
                    'username' => Str::slug(trim($firstName . ' ' . $lastName), '_'),
                    'user_email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'domain_uuid' => $domainUuid,
                    'user_enabled' => 'true',
                    'add_user' => auth()->user()?->username,
                    'extension_uuid' => $extension->extension_uuid,
                ]);

                $user->user_adv_fields()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]);

                $user->settings()->createMany([
                    [
                        'domain_uuid' => $domainUuid,
                        'user_setting_category' => 'domain',
                        'user_setting_subcategory' => 'language',
                        'user_setting_name' => 'code',
                        'user_setting_value' => get_domain_setting('language'),
                        'user_setting_enabled' => true,
                    ],
                    [
                        'domain_uuid' => $domainUuid,
                        'user_setting_category' => 'domain',
                        'user_setting_subcategory' => 'time_zone',
                        'user_setting_name' => 'name',
                        'user_setting_value' => get_local_time_zone($domainUuid),
                        'user_setting_enabled' => true,
                    ],
                ]);
            }

            $groupNames = $role === 'admin'
                ? ['user', 'Contact Center Supervisor']
                : ['user', 'Contact Center Agent'];

            $groups = Groups::query()->whereIn('group_name', $groupNames)->get();

            foreach ($groups as $group) {
                UserGroup::query()->firstOrCreate(
                    [
                        'group_uuid' => $group->group_uuid,
                        'user_uuid' => $user->user_uuid,
                    ],
                    [
                        'domain_uuid' => $domainUuid,
                        'group_name' => $group->group_name,
                    ]
                );
            }

            $agent = null;

            if ($role === 'agent') {
                $agent = CallCenterAgents::query()
                    ->where('domain_uuid', $domainUuid)
                    ->where('agent_id', $extension->extension)
                    ->first();

                if (! $agent) {
                    $agent = $this->createOrUpdateAgent($domainUuid, $domainName, [
                        'extension_uuid' => $extension->extension_uuid,
                        'agent_name' => null,
                        'agent_status' => 'Available',
                        'agent_call_timeout' => 20,
                        'agent_wrap_up_time' => 10,
                        'agent_reject_delay_time' => 10,
                    ]);
                }
            }

            return [
                'user' => $user,
                'agent' => $agent,
            ];
        });
    }

    public function createPauseReason(string $domainUuid, array $validated): CallCenterPauseReason
    {
        return CallCenterPauseReason::query()->updateOrCreate(
            [
                'domain_uuid' => $domainUuid,
                'code' => $validated['code'],
            ],
            [
                'name' => $validated['name'],
                'auto_resume_minutes' => $validated['auto_resume_minutes'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]
        );
    }

    public function pauseAgent(CallCenterAgents $agent, ?string $pauseReasonUuid, ?string $note = null): CallCenterAgentPause
    {
        CallCenterAgentPause::query()
            ->where('domain_uuid', $agent->domain_uuid)
            ->where('call_center_agent_uuid', $agent->call_center_agent_uuid)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        $pause = CallCenterAgentPause::query()->create([
            'domain_uuid' => $agent->domain_uuid,
            'call_center_agent_uuid' => $agent->call_center_agent_uuid,
            'pause_reason_uuid' => $pauseReasonUuid,
            'created_by_user_uuid' => auth()->user()?->user_uuid,
            'note' => $note,
            'started_at' => now(),
        ]);

        $agent->update(['agent_status' => 'On Break']);
        $this->notifyWallboard($agent->domain_uuid, [
            'event' => 'call-center.agent.paused',
            'agent_uuid' => $agent->call_center_agent_uuid,
        ]);

        return $pause->load('reason');
    }

    public function resumeAgent(CallCenterAgents $agent): void
    {
        CallCenterAgentPause::query()
            ->where('domain_uuid', $agent->domain_uuid)
            ->where('call_center_agent_uuid', $agent->call_center_agent_uuid)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        $agent->update(['agent_status' => 'Available']);
        $this->notifyWallboard($agent->domain_uuid, [
            'event' => 'call-center.agent.resumed',
            'agent_uuid' => $agent->call_center_agent_uuid,
        ]);
    }

    public function storeCallback(string $domainUuid, array $validated): CallCenterCallback
    {
        $callback = CallCenterCallback::query()->create([
            'domain_uuid' => $domainUuid,
            'call_center_queue_uuid' => $validated['call_center_queue_uuid'] ?? null,
            'call_center_agent_uuid' => $validated['call_center_agent_uuid'] ?? null,
            'dialer_lead_uuid' => $validated['dialer_lead_uuid'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'phone_number' => $validated['phone_number'],
            'state_code' => $validated['state_code'] ?? null,
            'timezone' => $validated['timezone'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'requested_at' => $validated['requested_at'] ?? now(),
            'preferred_callback_at' => $validated['preferred_callback_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'payload' => $validated['payload'] ?? null,
        ]);

        $this->notifyWallboard($domainUuid, [
            'event' => 'call-center.callback.created',
            'callback_uuid' => $callback->uuid,
        ]);

        return $callback;
    }

    public function updateCallback(CallCenterCallback $callback, array $validated): CallCenterCallback
    {
        $callback->update([
            'call_center_agent_uuid' => $validated['call_center_agent_uuid'] ?? $callback->call_center_agent_uuid,
            'status' => $validated['status'] ?? $callback->status,
            'preferred_callback_at' => $validated['preferred_callback_at'] ?? $callback->preferred_callback_at,
            'notes' => $validated['notes'] ?? $callback->notes,
            'completed_at' => ($validated['status'] ?? null) === 'completed' ? now() : $callback->completed_at,
        ]);

        $this->notifyWallboard($callback->domain_uuid, [
            'event' => 'call-center.callback.updated',
            'callback_uuid' => $callback->uuid,
            'status' => $callback->status,
        ]);

        return $callback->fresh(['queue', 'agent', 'lead']);
    }

    public function startMonitoring(string $domainUuid, string $domainName, array $validated): CallCenterMonitoringSession
    {
        $supervisorExtension = Extensions::query()
            ->where('domain_uuid', $domainUuid)
            ->whereKey($validated['supervisor_extension_uuid'])
            ->firstOrFail();

        $session = CallCenterMonitoringSession::query()->create([
            'domain_uuid' => $domainUuid,
            'call_center_agent_uuid' => $validated['call_center_agent_uuid'] ?? null,
            'supervisor_extension_uuid' => $supervisorExtension->extension_uuid,
            'call_uuid' => $validated['call_uuid'],
            'mode' => $validated['mode'],
            'status' => 'queued',
            'notes' => $validated['notes'] ?? null,
        ]);

        $command = $this->buildMonitoringCommand($supervisorExtension->extension, $domainName, $validated['call_uuid'], $validated['mode']);
        $response = (new FreeswitchEslService())->executeCommand($command);
        $jobUuid = is_array($response) ? ($response['job_uuid'] ?? null) : null;

        $session->update([
            'status' => $jobUuid ? 'started' : 'failed',
            'freeswitch_job_uuid' => $jobUuid,
            'started_at' => $jobUuid ? now() : null,
        ]);

        $this->notifyWallboard($domainUuid, [
            'event' => 'call-center.monitor.started',
            'monitor_uuid' => $session->uuid,
            'mode' => $validated['mode'],
            'call_uuid' => $validated['call_uuid'],
        ]);

        return $session->fresh(['agent', 'supervisorExtension']);
    }

    public function recordQueueEvent(array $payload): CallCenterEvent
    {
        $data = $payload['data'] ?? $payload;
        $domainUuid = $data['domain_uuid'] ?? session('domain_uuid');
        $queue = $this->resolveQueue($domainUuid, $data);
        $agent = $this->resolveAgent($domainUuid, $data);

        $event = CallCenterEvent::query()->create([
            'domain_uuid' => $domainUuid,
            'call_center_queue_uuid' => $queue?->call_center_queue_uuid,
            'call_center_agent_uuid' => $agent?->call_center_agent_uuid,
            'call_uuid' => $data['call_uuid'] ?? null,
            'member_uuid' => $data['member_uuid'] ?? null,
            'direction' => $data['direction'] ?? null,
            'event_type' => $data['event_type'] ?? $payload['event'] ?? 'queue.updated',
            'caller_number' => $data['caller_number'] ?? $data['caller_id_number'] ?? null,
            'wait_seconds' => isset($data['wait_seconds']) ? (int) $data['wait_seconds'] : null,
            'payload' => $data,
            'occurred_at' => isset($data['occurred_at']) ? CarbonImmutable::parse($data['occurred_at']) : now(),
        ]);

        if (($event->event_type === 'callback.requested' || ! empty($data['callback_requested'])) && ! empty($data['caller_number'])) {
            $this->storeCallback($domainUuid, [
                'call_center_queue_uuid' => $queue?->call_center_queue_uuid,
                'call_center_agent_uuid' => $agent?->call_center_agent_uuid,
                'contact_name' => $data['contact_name'] ?? null,
                'phone_number' => $data['caller_number'],
                'state_code' => $data['state_code'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'requested_at' => $event->occurred_at,
                'preferred_callback_at' => $data['preferred_callback_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'payload' => $data,
            ]);
        }

        $this->notifyWallboard($domainUuid, [
            'event' => 'call-center.event.recorded',
            'event_type' => $event->event_type,
        ]);

        return $event;
    }

    public function getWallboardData(
        string $domainUuid,
        string $domainName,
        ?Collection $queues = null,
        ?Collection $agents = null,
        ?Collection $channels = null
    ): array {
        $queues = $queues ?: CallCenterQueues::query()->where('domain_uuid', $domainUuid)->get();
        $agents = $agents ?: CallCenterAgents::query()->where('domain_uuid', $domainUuid)->get();
        $channels = $channels ?: collect();

        $queueRows = $queues->map(fn(CallCenterQueues $queue) => $this->queueWallboardRow($queue, $domainName))->values();
        $answered = $queueRows->sum('answered_count');
        $abandoned = $queueRows->sum('abandoned_count');
        $activeCalls = $this->getActiveCalls($domainUuid, $channels, $agents);
        $callbacks = $this->getCallbacks($domainUuid);

        return [
            'summary' => [
                'active_calls' => $activeCalls->count(),
                'waiting_members' => $queueRows->sum('live_waiting_members'),
                'available_agents' => $agents->where('agent_status', 'Available')->count(),
                'callbacks_pending' => $callbacks->where('status', 'pending')->count(),
                'service_level_percent' => $answered > 0
                    ? round($queueRows->sum('sla_hits') / max($answered, 1) * 100, 1)
                    : 0,
                'abandonment_rate' => ($answered + $abandoned) > 0
                    ? round($abandoned / ($answered + $abandoned) * 100, 1)
                    : 0,
                'avg_wait_seconds' => round($queueRows->avg('avg_wait_seconds') ?: 0, 1),
            ],
            'queues' => $queueRows,
            'active_calls' => $activeCalls->values(),
            'callbacks' => $callbacks->values(),
        ];
    }

    protected function getPauseReasons(string $domainUuid): Collection
    {
        return CallCenterPauseReason::query()
            ->where('is_active', true)
            ->where(function ($query) use ($domainUuid) {
                $query->where('domain_uuid', $domainUuid)
                    ->orWhere('domain_uuid', self::GLOBAL_DOMAIN_UUID);
            })
            ->orderBy('name')
            ->get()
            ->map(fn(CallCenterPauseReason $reason) => [
                'uuid' => $reason->uuid,
                'code' => $reason->code,
                'name' => $reason->name,
                'auto_resume_minutes' => $reason->auto_resume_minutes,
            ]);
    }

    protected function getCallbacks(string $domainUuid): Collection
    {
        return CallCenterCallback::query()
            ->with(['queue', 'agent', 'lead'])
            ->where('domain_uuid', $domainUuid)
            ->latest('requested_at')
            ->limit(50)
            ->get()
            ->map(fn(CallCenterCallback $callback) => [
                'uuid' => $callback->uuid,
                'call_center_queue_uuid' => $callback->call_center_queue_uuid,
                'call_center_agent_uuid' => $callback->call_center_agent_uuid,
                'contact_name' => $callback->contact_name,
                'phone_number' => $callback->phone_number,
                'status' => $callback->status,
                'state_code' => $callback->state_code,
                'timezone' => $callback->timezone,
                'requested_at' => $callback->requested_at,
                'preferred_callback_at' => $callback->preferred_callback_at,
                'notes' => $callback->notes,
                'queue_label' => $callback->queue ? ($callback->queue->queue_extension . ' - ' . $callback->queue->queue_name) : null,
                'agent_label' => $callback->agent ? ($callback->agent->agent_id . ' - ' . $callback->agent->agent_name) : null,
            ]);
    }

    protected function getAgentOptions(string $domainUuid): array
    {
        return CallCenterAgents::query()
            ->where('domain_uuid', $domainUuid)
            ->orderBy('agent_name')
            ->get()
            ->map(fn(CallCenterAgents $agent) => [
                'value' => $agent->call_center_agent_uuid,
                'label' => $agent->agent_name,
                'extension' => $agent->agent_id,
            ])
            ->values()
            ->all();
    }

    protected function getExtensionOptions(string $domainUuid): array
    {
        return Extensions::query()
            ->where('domain_uuid', $domainUuid)
            ->orderBy('extension')
            ->get(['extension_uuid', 'extension', 'effective_caller_id_name'])
            ->map(fn(Extensions $extension) => [
                'value' => $extension->extension_uuid,
                'label' => $extension->extension . ' - ' . ($extension->effective_caller_id_name ?: $extension->extension),
            ])
            ->values()
            ->all();
    }

    protected function getLiveRuntimeState(): array
    {
        $registeredExtensions = collect();
        $busyExtensions = collect();
        $channels = collect();

        try {
            $esl = new FreeswitchEslService();
            $registeredExtensions = $esl->getAllSipRegistrations()->pluck('user')->filter()->unique()->values();
            $channels = $esl->getAllChannels();
            $busyExtensions = $channels
                ->flatMap(fn(array $channel) => [
                    $channel['cid_num'] ?? null,
                    $channel['callee_num'] ?? null,
                    $channel['dest'] ?? null,
                    $channel['initial_cid_num'] ?? null,
                ])
                ->filter()
                ->unique()
                ->values();
        } catch (Throwable $error) {
            logger()->warning('Call center live stats unavailable: ' . $error->getMessage());
        }

        return [$registeredExtensions, $busyExtensions, $channels];
    }

    protected function getActiveCalls(string $domainUuid, Collection $channels, ?Collection $agents = null): Collection
    {
        $agents = $agents ?: CallCenterAgents::query()->where('domain_uuid', $domainUuid)->get();
        $agentExtensions = $agents->pluck('call_center_agent_uuid', 'agent_id');

        return $channels
            ->filter(function (array $channel) use ($agentExtensions) {
                return $agentExtensions->has($channel['cid_num'] ?? '')
                    || $agentExtensions->has($channel['callee_num'] ?? '')
                    || $agentExtensions->has($channel['initial_cid_num'] ?? '');
            })
            ->map(function (array $channel) use ($agentExtensions) {
                $extension = $channel['cid_num'] ?? $channel['callee_num'] ?? $channel['initial_cid_num'] ?? null;

                return [
                    'call_uuid' => $channel['call_uuid'] ?? $channel['uuid'] ?? null,
                    'agent_extension' => $extension,
                    'agent_uuid' => $agentExtensions->get($extension),
                    'destination' => $channel['dest'] ?? $channel['callee_num'] ?? null,
                    'direction' => $channel['direction'] ?? null,
                    'state' => $channel['callstate'] ?? $channel['state'] ?? null,
                    'duration_seconds' => isset($channel['created_epoch'])
                        ? max(0, now()->timestamp - (int) $channel['created_epoch'])
                        : null,
                ];
            })
            ->unique('call_uuid')
            ->values();
    }

    protected function queueWallboardRow(CallCenterQueues $queue, string $domainName): array
    {
        $todayEvents = CallCenterEvent::query()
            ->where('domain_uuid', $queue->domain_uuid)
            ->where('call_center_queue_uuid', $queue->call_center_queue_uuid)
            ->whereDate('occurred_at', today())
            ->get();

        $answeredCount = $todayEvents->where('event_type', 'member.answered')->count();
        $abandonedCount = $todayEvents->where('event_type', 'member.abandoned')->count();
        $slaTarget = (int) ($queue->queue_max_wait_time ?: 20);
        $slaHits = $todayEvents
            ->where('event_type', 'member.answered')
            ->where('wait_seconds', '<=', $slaTarget)
            ->count();

        return [
            'uuid' => $queue->call_center_queue_uuid,
            'name' => $queue->queue_name,
            'extension' => $queue->queue_extension,
            'answered_count' => $answeredCount,
            'abandoned_count' => $abandonedCount,
            'sla_hits' => $slaHits,
            'live_waiting_members' => $this->callCenterCount("callcenter_config queue count members {$this->queueIdentifier($queue, $domainName)}"),
            'live_agent_slots' => $this->callCenterCount("callcenter_config queue count agents {$this->queueIdentifier($queue, $domainName)}"),
            'service_level_percent' => $answeredCount > 0 ? round($slaHits / $answeredCount * 100, 1) : 0,
            'abandonment_rate' => ($answeredCount + $abandonedCount) > 0
                ? round($abandonedCount / ($answeredCount + $abandonedCount) * 100, 1)
                : 0,
            'avg_wait_seconds' => round((float) ($todayEvents->avg('wait_seconds') ?: 0), 1),
        ];
    }

    protected function callCenterCount(string $command): int
    {
        try {
            $response = (new FreeswitchEslService())->executeCommand($command);

            if (is_numeric($response)) {
                return (int) $response;
            }

            if (is_string($response) && preg_match('/(\d+)/', $response, $matches)) {
                return (int) $matches[1];
            }
        } catch (Throwable $error) {
            logger()->warning('Unable to fetch call center queue count: ' . $error->getMessage());
        }

        return 0;
    }

    protected function queueIdentifier(CallCenterQueues $queue, string $domainName): string
    {
        return str_contains($queue->queue_name, '@')
            ? $queue->queue_name
            : $queue->queue_name . '@' . $domainName;
    }

    protected function buildMonitoringCommand(string $extension, string $domainName, string $callUuid, string $mode): string
    {
        $endpoint = "user/{$extension}@{$domainName}";

        return match ($mode) {
            'whisper' => "bgapi originate {$endpoint} 'queue_dtmf:w2@500,eavesdrop:{$callUuid}' inline",
            'barge' => "bgapi originate {$endpoint} &three_way({$callUuid})",
            default => "bgapi originate {$endpoint} &eavesdrop({$callUuid})",
        };
    }

    protected function resolveQueue(string $domainUuid, array $payload): ?CallCenterQueues
    {
        $queueUuid = $payload['call_center_queue_uuid'] ?? $payload['queue_uuid'] ?? null;

        if ($queueUuid) {
            return CallCenterQueues::query()
                ->where('domain_uuid', $domainUuid)
                ->where('call_center_queue_uuid', $queueUuid)
                ->first();
        }

        if (! empty($payload['queue_extension'])) {
            return CallCenterQueues::query()
                ->where('domain_uuid', $domainUuid)
                ->where('queue_extension', $payload['queue_extension'])
                ->first();
        }

        return null;
    }

    protected function resolveAgent(string $domainUuid, array $payload): ?CallCenterAgents
    {
        $agentUuid = $payload['call_center_agent_uuid'] ?? $payload['agent_uuid'] ?? null;

        if ($agentUuid) {
            return CallCenterAgents::query()
                ->where('domain_uuid', $domainUuid)
                ->where('call_center_agent_uuid', $agentUuid)
                ->first();
        }

        if (! empty($payload['agent_extension'])) {
            return CallCenterAgents::query()
                ->where('domain_uuid', $domainUuid)
                ->where('agent_id', $payload['agent_extension'])
                ->first();
        }

        return null;
    }

    protected function notifyWallboard(string $domainUuid, array $payload = []): void
    {
        $message = array_merge([
            'domain_uuid' => $domainUuid,
            'timestamp' => now()->toIso8601String(),
        ], $payload);

        broadcast(new CallCenterWallboardUpdated($domainUuid, $message));

        try {
            activity()
                ->withProperties($message)
                ->event($payload['event'] ?? 'call-center.updated')
                ->log('Call center activity recorded');
        } catch (Throwable $error) {
            logger()->warning('Unable to write call center activity log: ' . $error->getMessage());
        }
    }
}
