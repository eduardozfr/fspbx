<?php

namespace Modules\Dialer\Models;

use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class DialerCampaignLead extends Model
{
    use TraitUuid;

    protected $table = 'dialer_campaign_leads';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'callback_due_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(DialerCampaign::class, 'campaign_uuid', 'uuid');
    }

    public function lead()
    {
        return $this->belongsTo(DialerLead::class, 'lead_uuid', 'uuid');
    }
}
