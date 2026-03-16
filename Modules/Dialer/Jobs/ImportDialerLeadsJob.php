<?php

namespace Modules\Dialer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Modules\Dialer\Models\DialerImportBatch;
use Modules\Dialer\Services\DialerService;
use Spatie\SimpleExcel\SimpleExcelReader;
use Throwable;

class ImportDialerLeadsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public string $batchUuid
    ) {}

    public function handle(DialerService $dialerService): void
    {
        $batch = DialerImportBatch::query()->find($this->batchUuid);

        if (! $batch) {
            return;
        }

        $batch->update([
            'status' => 'processing',
            'errors' => [],
        ]);

        $path = Storage::disk('local')->path($batch->file_path);
        $rows = SimpleExcelReader::create($path)->getRows();

        $stats = [
            'total_rows' => 0,
            'imported_rows' => 0,
            'skipped_rows' => 0,
            'error_rows' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $stats['total_rows']++;

            try {
                $dialerService->importLeadRow($batch, Arr::undot($row), $index + 1);
                $stats['imported_rows']++;
            } catch (Throwable $error) {
                $stats['error_rows']++;
                $stats['errors'][] = [
                    'row' => $index + 1,
                    'message' => $error->getMessage(),
                ];
            }
        }

        $batch->update([
            'status' => $stats['error_rows'] > 0 ? 'completed_with_errors' : 'completed',
            'total_rows' => $stats['total_rows'],
            'imported_rows' => $stats['imported_rows'],
            'skipped_rows' => $stats['skipped_rows'],
            'error_rows' => $stats['error_rows'],
            'errors' => $stats['errors'],
            'completed_at' => now(),
        ]);
    }
}
