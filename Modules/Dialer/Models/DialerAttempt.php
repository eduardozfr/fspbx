<?php

namespace Modules\Dialer\Models;

use App\Models\CallCenterQueues;
use App\Models\Extensions;
use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class DialerAttempt extends Model
{
    use TraitUuid;

    protected $table = 'dialer_attempts';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'answered_at' => 'datetime',
        'completed_at' => 'datetime',
        'webhook_dispatched_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(DialerCampaign::class, 'campaign_uuid', 'uuid');
    }

    public function lead()
    {
        return $this->belongsTo(DialerLead::class, 'lead_uuid', 'uuid');
    }

    public function extension()
    {
        return $this->belongsTo(Extensions::class, 'extension_uuid', 'extension_uuid');
    }

    public function queue()
    {
        return $this->belongsTo(CallCenterQueues::class, 'call_center_queue_uuid', 'call_center_queue_uuid');
    }

    public function dispositionModel()
    {
        return $this->belongsTo(DialerDisposition::class, 'disposition_uuid', 'uuid');
    }
}
