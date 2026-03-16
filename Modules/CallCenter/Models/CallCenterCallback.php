<?php

namespace Modules\CallCenter\Models;

use App\Models\CallCenterAgents;
use App\Models\CallCenterQueues;
use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;
use Modules\Dialer\Models\DialerLead;

class CallCenterCallback extends Model
{
    use TraitUuid;

    protected $table = 'call_center_callbacks';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'preferred_callback_at' => 'datetime',
        'completed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function queue()
    {
        return $this->belongsTo(CallCenterQueues::class, 'call_center_queue_uuid', 'call_center_queue_uuid');
    }

    public function agent()
    {
        return $this->belongsTo(CallCenterAgents::class, 'call_center_agent_uuid', 'call_center_agent_uuid');
    }

    public function lead()
    {
        return $this->belongsTo(DialerLead::class, 'dialer_lead_uuid', 'uuid');
    }
}
