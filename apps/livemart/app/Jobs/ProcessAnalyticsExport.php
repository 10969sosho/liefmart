<?php

namespace App\Jobs;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Models\ExportLog;
use App\Notifications\ExportReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessAnalyticsExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes for large exports
    public int $maxExceptions = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $exportType,
        public array $filters,
        public int $userId,
        public int $exportLogId,
    ) {
        $this->onQueue('exports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exportLog = ExportLog::findOrFail($this->exportLogId);
        $exportLog->markAsProcessing();

        try {
            // Increase limits for large exports
            set_time_limit(600);
            ini_set('memory_limit', '2048M');

            $handler = $this->resolveHandler();

            if (!$handler) {
                throw new \RuntimeException("Unknown export type: {$this->exportType}");
            }

            $result = $handler->handle($this->filters);

            // Generate the Excel file and save to storage
            $filePath = 'exports/' . $result['filename'];
            Excel::store($result['export'], $filePath, 'local');

            // Get file size
            $fileSize = Storage::disk('local')->size($filePath);

            // Update export log
            $exportLog->markAsCompleted($filePath, $result['filename'], $fileSize);

            // Notify user
            $user = $exportLog->user;
            if ($user) {
                $user->notify(new ExportReadyNotification($exportLog));
            }

            Log::info("Export completed: {$this->exportType}", [
                'user_id' => $this->userId,
                'file' => $result['filename'],
                'size' => $fileSize,
            ]);

        } catch (\Throwable $e) {
            $exportLog->markAsFailed($e->getMessage());

            // Notify user about failure
            $user = $exportLog->user;
            if ($user) {
                $user->notify(new ExportReadyNotification($exportLog));
            }

            Log::error("Export failed: {$this->exportType}", [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve the handler for this export type.
     */
    private function resolveHandler(): ?ExportHandlerInterface
    {
        $handlerClass = config("analytics-export-handlers.{$this->exportType}");

        if (!$handlerClass || !class_exists($handlerClass)) {
            return null;
        }

        return app($handlerClass);
    }
}
