<?php

use App\Support\BrazilianDialerStateRules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dialer_state_rules')) {
            return;
        }

        $rows = BrazilianDialerStateRules::seedRows();
        $codes = collect($rows)->pluck('state_code')->all();

        DB::table('dialer_state_rules')
            ->whereNotIn('state_code', $codes)
            ->delete();

        foreach ($rows as $row) {
            $existing = DB::table('dialer_state_rules')
                ->where('state_code', $row['state_code'])
                ->first();

            if ($existing) {
                DB::table('dialer_state_rules')
                    ->where('state_code', $row['state_code'])
                    ->update([
                        'state_name' => $row['state_name'],
                        'timezone' => $row['timezone'],
                        'schedule' => $row['schedule'],
                        'notes' => $row['notes'],
                        'legal_reference_url' => $row['legal_reference_url'],
                        'updated_at' => $row['updated_at'],
                    ]);

                continue;
            }

            DB::table('dialer_state_rules')->insert($row);
        }
    }

    public function down(): void
    {
        // Keep the BR rules in place; rolling back should not restore the legacy US defaults.
    }
};
