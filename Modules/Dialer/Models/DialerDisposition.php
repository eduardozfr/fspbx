<?php

namespace Modules\Dialer\Models;

use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class DialerDisposition extends Model
{
    use TraitUuid;

    protected $table = 'dialer_dispositions';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'is_final' => 'boolean',
        'is_callback' => 'boolean',
        'mark_dnc' => 'boolean',
        'auto_close_lead' => 'boolean',
    ];
}
