<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Dialer\Models\DialerCampaign;
use Modules\Dialer\Services\DialerService;

class RunDialerCampaigns extends Command
{
    protected $signature = 'dialer:run';

    protected $description = 'Dispatch automatic dialer campaigns that are ready to place calls';

    public function handle(DialerService $dialerService): int
    {
        if (! Schema::hasTable('dialer_campaigns')) {
            $this->warn('Dialer tables are not available yet.');
            return self::SUCCESS;
        }

        $campaigns = DialerCampaign::query()
            ->whereIn('mode', ['progressive', 'power'])
            ->where('status', 'active')
            ->get();

        foreach ($campaigns as $campaign) {
            try {
                $result = $dialerService->processCampaign($campaign);
                $this->info("{$campaign->name}: {$result['dialed']} call(s) dispatched.");
            } catch (\Throwable $error) {
                $this->warn("{$campaign->name}: {$error->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
