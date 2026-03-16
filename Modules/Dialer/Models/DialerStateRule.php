<?php

namespace Modules\Dialer\Models;

use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class DialerStateRule extends Model
{
    use TraitUuid;

    protected $table = 'dialer_state_rules';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'schedule' => 'array',
    ];
}
