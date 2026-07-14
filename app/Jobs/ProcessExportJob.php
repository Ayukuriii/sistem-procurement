<?php

namespace App\Jobs;

use App\Constants\Exports;
use App\Mail\ExportReadyMail;
use App\Models\ExportJob;
use App\Services\ExportImport\ModuleHandlerRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessExportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $exportJobId
    ) {}

    public function handle(ModuleHandlerRegistry $registry): void
    {
        $exportJob = ExportJob::query()->with('user')->find($this->exportJobId);

        if (! $exportJob) {
            return;
        }

        $exportJob->update([
            'status' => Exports::STATUS_PROCESSING,
            'failure_reason' => null,
        ]);

        try {
            $handler = $registry->exportable($exportJob->module);
            $exporter = $handler->makeExporter($exportJob->filters ?? []);
            $relativePath = Exports::STORAGE_DIR.'/'.$exportJob->user_id.'/'.$exportJob->public_id.'.xlsx';

            Excel::store($exporter, $relativePath, Exports::STORAGE_DISK);

            $downloadUrl = Storage::disk(Exports::STORAGE_DISK)->url($relativePath);

            $exportJob->update([
                'status' => Exports::STATUS_COMPLETED,
                'disk' => Exports::STORAGE_DISK,
                'path' => $relativePath,
                'download_url' => $downloadUrl,
                'completed_at' => now(),
            ]);

            if ($exportJob->user?->email) {
                Mail::to($exportJob->user->email)->send(new ExportReadyMail($exportJob->fresh('user')));
            }
        } catch (Throwable $e) {
            $exportJob->update([
                'status' => Exports::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
