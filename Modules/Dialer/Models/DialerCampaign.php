<?php

namespace Modules\Dialer\Models;

use App\Models\CallCenterQueues;
use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class DialerCampaign extends Model
{
    use TraitUuid;

    protected $table = 'dialer_campaigns';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'pacing_ratio' => 'decimal:2',
        'max_inflight_calls' => 'integer',
        'last_executed_at' => 'datetime',
        'respect_dnc' => 'boolean',
        'amd_enabled' => 'boolean',
    ];

    public function queue()
    {
        return $this->belongsTo(CallCenterQueues::class, 'call_center_queue_uuid', 'call_center_queue_uuid');
    }

    public function complianceProfile()
    {
        return $this->belongsTo(DialerComplianceProfile::class, 'dialer_compliance_profile_uuid', 'uuid');
    }

    public function campaignLeads()
    {
        return $this->hasMany(DialerCampaignLead::class, 'campaign_uuid', 'uuid');
    }

    public function attempts()
    {
        return $this->hasMany(DialerAttempt::class, 'campaign_uuid', 'uuid');
    }

    public function importBatches()
    {
        return $this->hasMany(DialerImportBatch::class, 'campaign_uuid', 'uuid');
    }
}
