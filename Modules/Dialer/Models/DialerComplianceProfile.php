<?php

namespace Modules\Dialer\Models;

use App\Models\Traits\TraitUuid;
use Illuminate\Database\Eloquent\Model;

class DialerComplianceProfile extends Model
{
    use TraitUuid;

    protected $table = 'dialer_compliance_profiles';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'schedule' => 'array',
        'state_codes' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function campaigns()
    {
        return $this->hasMany(DialerCampaign::class, 'dialer_compliance_profile_uuid', 'uuid');
    }
}
