<?php

namespace Modules\CallCenter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CallCenterAgents;
use App\Models\CallCenterQueues;
use App\Rules\UniqueExtension;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\CallCenter\Models\CallCenterCallback;
use Modules\CallCenter\Services\CallCenterService;

class CallCenterController extends Controller
{
    public function __construct(
        protected CallCenterService $service
    ) {}

    public function index()
    {
        if (! userCheckPermission('contact_center_dashboard_view') && ! userCheckPermission('contact_center_settings_view')) {
            return redirect('/');
        }

        return $this->renderPage('operations');
    }

    public function settings()
    {
        if (! userCheckPermission('contact_center_settings_view') && ! userCheckPermission('contact_center_settings_edit')) {
            return redirect('/');
        }

        return $this->renderPage('configuration');
    }

    public function storeQueue(Request $request): JsonResponse
    {
        if (! userCheckPermission('contact_center_queues_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'queue_name' => ['required', 'string', 'max:255'],
            'queue_extension' => ['required', 'string', 'max:20', new UniqueExtension()],
            'queue_strategy' => ['required', 'string', 'max:50'],
            'queue_description' => ['nullable', 'string', 'max:500'],
            'queue_max_wait_time' => ['nullable', 'integer', 'min:0'],
            'agent_uuids' => ['nullable', 'array'],
            'agent_uuids.*' => ['uuid'],
        ]);

        $queue = CallCenterQueues::query()->create([
            'domain_uuid' => session('domain_uuid'),
            'queue_name' => $validated['queue_name'],
            'queue_extension' => $validated['queue_extension'],
            'queue_strategy' => $validated['queue_strategy'],
            'queue_description' => $validated['queue_description'] ?? null,
            'queue_max_wait_time' => $validated['queue_max_wait_time'] ?? 0,
        ]);

        $this->service->syncQueueAgents($queue, $validated['agent_uuids'] ?? []);

        return response()->json([
            'messages' => ['success' => [__('Queue created successfully.')]],
        ]);
    }

    public function updateQueue(Request $request, CallCenterQueues $queue): JsonResponse
    {
        if (! userCheckPermission('contact_center_queues_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($queue->domain_uuid === session('domain_uuid'), 404);

        $validated = $request->validate([
            'queue_name' => ['required', 'string', 'max:255'],
            'queue_extension' => ['required', 'string', 'max:20', new UniqueExtension($queue->call_center_queue_uuid)],
            'queue_strategy' => ['required', 'string', 'max:50'],
            'queue_description' => ['nullable', 'string', 'max:500'],
            'queue_max_wait_time' => ['nullable', 'integer', 'min:0'],
            'agent_uuids' => ['nullable', 'array'],
            'agent_uuids.*' => ['uuid'],
        ]);

        $queue->update([
            'queue_name' => $validated['queue_name'],
            'queue_extension' => $validated['queue_extension'],
            'queue_strategy' => $validated['queue_strategy'],
            'queue_description' => $validated['queue_description'] ?? null,
            'queue_max_wait_time' => $validated['queue_max_wait_time'] ?? 0,
        ]);

        $this->service->syncQueueAgents($queue, $validated['agent_uuids'] ?? []);

        return response()->json([
            'messages' => ['success' => [__('Queue updated successfully.')]],
        ]);
    }

    public function destroyQueue(CallCenterQueues $queue): JsonResponse
    {
        if (! userCheckPermission('contact_center_queues_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($queue->domain_uuid === session('domain_uuid'), 404);

        $queue->agents()->detach();
        $queue->delete();

        return response()->json([
            'messages' => ['success' => [__('Queue deleted successfully.')]],
        ]);
    }

    public function storeAgent(Request $request): JsonResponse
    {
        if (! userCheckPermission('contact_center_agents_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'extension_uuid' => ['required', 'uuid'],
            'agent_name' => ['nullable', 'string', 'max:255'],
            'agent_status' => ['required', 'string', 'max:50'],
            'agent_call_timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
            'agent_wrap_up_time' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'agent_reject_delay_time' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $agent = $this->service->createOrUpdateAgent(
            session('domain_uuid'),
            session('domain_name'),
            $validated
        );

        return response()->json([
            'messages' => ['success' => [__('Agent created successfully.')]],
            'agent_uuid' => $agent->call_center_agent_uuid,
        ]);
    }

    public function updateAgent(Request $request, CallCenterAgents $agent): JsonResponse
    {
        if (! userCheckPermission('contact_center_agents_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($agent->domain_uuid === session('domain_uuid'), 404);

        $validated = $request->validate([
            'extension_uuid' => ['required', 'uuid'],
            'agent_name' => ['nullable', 'string', 'max:255'],
            'agent_status' => ['required', 'string', 'max:50'],
            'agent_call_timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
            'agent_wrap_up_time' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'agent_reject_delay_time' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $this->service->createOrUpdateAgent(
            session('domain_uuid'),
            session('domain_name'),
            $validated,
            $agent
        );

        return response()->json([
            'messages' => ['success' => [__('Agent updated successfully.')]],
        ]);
    }

    public function destroyAgent(CallCenterAgents $agent): JsonResponse
    {
        if (! userCheckPermission('contact_center_agents_manage')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($agent->domain_uuid === session('domain_uuid'), 404);

        $agent->queues()->detach();
        $agent->delete();

        return response()->json([
            'messages' => ['success' => [__('Agent deleted successfully.')]],
        ]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        if (! userCheckPermission('contact_center_agents_manage') && ! userCheckPermission('contact_center_settings_edit')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'extension_uuid' => ['required', 'uuid'],
            'role' => ['required', Rule::in(['agent', 'admin'])],
        ]);

        $result = $this->service->provisionUserFromExtension(
            session('domain_uuid'),
            session('domain_name'),
            $validated['extension_uuid'],
            $validated['role']
        );

        return response()->json([
            'messages' => ['success' => [$validated['role'] === 'agent'
                ? __('Contact center agent provisioned successfully.')
                : __('Contact center supervisor provisioned successfully.')]],
            'user_uuid' => $result['user']->user_uuid,
            'agent_uuid' => $result['agent']?->call_center_agent_uuid,
        ]);
    }

    public function storePauseReason(Request $request): JsonResponse
    {
        if (! userCheckPermission('contact_center_settings_edit')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'auto_resume_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->service->createPauseReason(session('domain_uuid'), $validated);

        return response()->json([
            'messages' => ['success' => [__('Pause reason saved successfully.')]],
        ]);
    }

    public function pauseAgentStatus(Request $request, CallCenterAgents $agent): JsonResponse
    {
        if (! userCheckPermission('contact_center_agents_manage') && ! userCheckPermission('contact_center_settings_edit')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($agent->domain_uuid === session('domain_uuid'), 404);

        $validated = $request->validate([
            'pause_reason_uuid' => ['nullable', 'uuid'],
            'note' => ['nullable', 'string'],
        ]);

        $this->service->pauseAgent($agent, $validated['pause_reason_uuid'] ?? null, $validated['note'] ?? null);

        return response()->json([
            'messages' => ['success' => [__('Agent paused successfully.')]],
        ]);
    }

    public function resumeAgentStatus(CallCenterAgents $agent): JsonResponse
    {
        if (! userCheckPermission('contact_center_agents_manage') && ! userCheckPermission('contact_center_settings_edit')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($agent->domain_uuid === session('domain_uuid'), 404);
        $this->service->resumeAgent($agent);

        return response()->json([
            'messages' => ['success' => [__('Agent resumed successfully.')]],
        ]);
    }

    public function storeCallback(Request $request): JsonResponse
    {
        if (! userCheckPermission('contact_center_settings_edit')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'call_center_queue_uuid' => ['nullable', 'uuid'],
            'call_center_agent_uuid' => ['nullable', 'uuid'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:50'],
            'state_code' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'preferred_callback_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->service->storeCallback(session('domain_uuid'), $validated);

        return response()->json([
            'messages' => ['success' => [__('Callback saved successfully.')]],
        ]);
    }

    public function updateCallback(Request $request, CallCenterCallback $callback): JsonResponse
    {
        if (! userCheckPermission('contact_center_settings_edit')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        abort_unless($callback->domain_uuid === session('domain_uuid'), 404);

        $validated = $request->validate([
            'call_center_agent_uuid' => ['nullable', 'uuid'],
            'status' => ['nullable', Rule::in(['pending', 'assigned', 'completed', 'canceled'])],
            'preferred_callback_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->service->updateCallback($callback, $validated);

        return response()->json([
            'messages' => ['success' => [__('Callback updated successfully.')]],
        ]);
    }

    public function startMonitoring(Request $request): JsonResponse
    {
        if (! userCheckPermission('contact_center_settings_edit')) {
            return response()->json(['messages' => ['error' => [__('Forbidden.')]]], 403);
        }

        $validated = $request->validate([
            'call_uuid' => ['required', 'string', 'max:255'],
            'call_center_agent_uuid' => ['nullable', 'uuid'],
            'supervisor_extension_uuid' => ['required', 'uuid'],
            'mode' => ['required', Rule::in(['listen', 'whisper', 'barge'])],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $session = $this->service->startMonitoring(
                session('domain_uuid'),
                session('domain_name'),
                $validated
            );
        } catch (\Throwable $error) {
            return response()->json(['messages' => ['error' => [__($error->getMessage())]]], 422);
        }

        return response()->json([
            'messages' => ['success' => [__('Monitoring session started successfully.')]],
            'monitor_uuid' => $session->uuid,
        ]);
    }

    protected function renderPage(string $initialTab)
    {
        $data = $this->service->getOverview(
            session('domain_uuid'),
            session('domain_name')
        );

        return Inertia::render('Index::CallCenter', [
            'summary' => $data['summary'],
            'queues' => $data['queues'],
            'agents' => $data['agents'],
            'wallboard' => $data['wallboard'],
            'callbacks' => $data['callbacks'],
            'pauseReasons' => $data['pause_reasons'],
            'alerts' => $data['alerts'],
            'pauseBreakdown' => $data['pause_breakdown'],
            'options' => $data['options'],
            'initialTab' => $initialTab,
            'routes' => [
                'index' => route('contact-center.index'),
                'settings' => route('contact-center.settings'),
                'storeQueue' => route('contact-center.queues.store'),
                'updateQueue' => route('contact-center.queues.update', ['queue' => '__QUEUE__']),
                'destroyQueue' => route('contact-center.queues.destroy', ['queue' => '__QUEUE__']),
                'storeAgent' => route('contact-center.agents.store'),
                'updateAgent' => route('contact-center.agents.update', ['agent' => '__AGENT__']),
                'destroyAgent' => route('contact-center.agents.destroy', ['agent' => '__AGENT__']),
                'pauseAgent' => route('contact-center.agents.pause', ['agent' => '__AGENT__']),
                'resumeAgent' => route('contact-center.agents.resume', ['agent' => '__AGENT__']),
                'storeUser' => route('contact-center.user.store'),
                'storePauseReason' => route('contact-center.pause-reasons.store'),
                'storeCallback' => route('contact-center.callbacks.store'),
                'updateCallback' => route('contact-center.callbacks.update', ['callback' => '__CALLBACK__']),
                'startMonitoring' => route('contact-center.monitoring.store'),
            ],
        ]);
    }
}
