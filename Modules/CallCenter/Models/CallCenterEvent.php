<?php

namespace Modules\CallCenter\Models;

use App\Models\CallCenterAgents;
use App\Models\CallCenterQueues;
use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class CallCenterEvent extends Model
{
    use TraitUuid;

    protected $table = 'call_center_events';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function queue()
    {
        return $this->belongsTo(CallCenterQueues::class, 'call_center_queue_uuid', 'call_center_queue_uuid');
    }

    public function agent()
    {
        return $this->belongsTo(CallCenterAgents::class, 'call_center_agent_uuid', 'call_center_agent_uuid');
    }
}
