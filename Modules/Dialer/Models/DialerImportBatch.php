<?php

namespace Modules\Dialer\Models;

use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class DialerImportBatch extends Model
{
    use TraitUuid;

    protected $table = 'dialer_import_batches';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
        'errors' => 'array',
        'completed_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(DialerCampaign::class, 'campaign_uuid', 'uuid');
    }
}
