<?php

namespace Modules\CallCenter\Models;

use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class CallCenterPauseReason extends Model
{
    use TraitUuid;

    protected $table = 'call_center_pause_reasons';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
