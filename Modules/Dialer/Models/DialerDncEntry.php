<?php

namespace Modules\Dialer\Models;

use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class DialerDncEntry extends Model
{
    use TraitUuid;

    protected $table = 'dialer_dnc_entries';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];
}
