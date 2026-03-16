<?php

namespace Modules\CallCenter\Models;

use App\Models\CallCenterAgents;
use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class CallCenterAgentPause extends Model
{
    use TraitUuid;

    protected $table = 'call_center_agent_pauses';

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

    public function reason()
    {
        return $this->belongsTo(CallCenterPauseReason::class, 'pause_reason_uuid', 'uuid');
    }
}
