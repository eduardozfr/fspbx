<?php

namespace Modules\Dialer\Models;

use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class DialerLead extends Model
{
    use TraitUuid;

    protected $table = 'dialer_leads';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'do_not_call' => 'boolean',
        'callback_requested_at' => 'datetime',
        'callback_due_at' => 'datetime',
    ];

    public function campaignLinks()
    {
        return $this->hasMany(DialerCampaignLead::class, 'lead_uuid', 'uuid');
    }

    public function attempts()
    {
        return $this->hasMany(DialerAttempt::class, 'lead_uuid', 'uuid');
    }

    public function importBatch()
    {
        return $this->belongsTo(DialerImportBatch::class, 'import_batch_uuid', 'uuid');
    }
}
