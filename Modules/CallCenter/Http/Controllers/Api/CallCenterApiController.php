<?php

namespace Modules\CallCenter\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallCenterAgents;
use App\Models\CallCenterQueues;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\CallCenter\Services\CallCenterService;

class CallCenterApiController extends Controller
{
    public function __construct(
        protected CallCenterService $service
    ) {}

    public function queues(): JsonResponse
    {
        abort_unless(userCheckPermission('contact_center_dashboard_view') || userCheckPermission('contact_center_settings_view'), 403);

        return response()->json(
            CallCenterQueues::query()
                ->with('agents')
                ->where('domain_uuid', $this->domainUuid())
                ->orderBy('queue_extension')
                ->get()
        );
    }

    public function showQueue(CallCenterQueues $queue): JsonResponse
    {
        abort_unless(userCheckPermission('contact_center_dashboard_view') || userCheckPermission('contact_center_settings_view'), 403);
        abort_unless($queue->domain_uuid === $this->domainUuid(), 404);

        return response()->json($queue->load('agents'));
    }

    public function agents(): JsonResponse
    {
        abort_unless(userCheckPermission('contact_center_dashboard_view') || userCheckPermission('contact_center_settings_view'), 403);

        return response()->json(
            CallCenterAgents::query()
                ->with('queues')
                ->where('domain_uuid', $this->domainUuid())
                ->orderBy('agent_name')
                ->get()
        );
    }

    public function showAgent(CallCenterAgents $agent): JsonResponse
    {
        abort_unless(userCheckPermission('contact_center_dashboard_view') || userCheckPermission('contact_center_settings_view'), 403);
        abort_unless($agent->domain_uuid === $this->domainUuid(), 404);

        return response()->json($agent->load('queues'));
    }

    public function wallboard(Request $request): JsonResponse
    {
        abort_unless(userCheckPermission('contact_center_dashboard_view') || userCheckPermission('contact_center_settings_view'), 403);
        $domainUuid = $this->domainUuid();
        $domainName = session('domain_name')
            ?: Domain::query()->where('domain_uuid', $domainUuid)->value('domain_name')
            ?: config('app.name');

        return response()->json(
            $this->service->getWallboardData(
                $domainUuid,
                $domainName
            )
        );
    }

    protected function domainUuid(): string
    {
        return auth()->user()?->domain_uuid ?? session('domain_uuid');
    }
}
