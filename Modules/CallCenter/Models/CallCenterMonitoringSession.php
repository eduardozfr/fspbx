<?php

namespace Modules\CallCenter\Models;

use App\Models\CallCenterAgents;
use App\Models\Extensions;
use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class CallCenterMonitoringSession extends Model
{
    use TraitUuid;

    protected $table = 'call_center_monitoring_sessions';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(CallCenterAgents::class, 'call_center_agent_uuid', 'call_center_agent_uuid');
    }

    public function supervisorExtension()
    {
        return $this->belongsTo(Extensions::class, 'supervisor_extension_uuid', 'extension_uuid');
    }
}
