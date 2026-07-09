<?php

namespace App\Http\Controllers\Analytics\Traits;

use App\Jobs\ProcessAnalyticsExport;
use App\Models\ExportLog;

trait DispatchesAnalyticsExport
{
    /**
     * Dispatch an analytics export to the queue and return appropriate response.
     *
     * @param string $type The export type (must match config/analytics-export-handlers.php key)
     * @param array $filters The filter parameters
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function dispatchExport(string $type, array $filters)
    {
        $exportLog = ExportLog::create([
            'user_id' => auth()->id(),
            'type' => $type,
            'filters' => $filters,
            'status' => 'pending',
        ]);

        ProcessAnalyticsExport::dispatch(
            $type,
            $filters,
            auth()->id(),
            $exportLog->id
        );

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Export ' . $exportLog->type_label . ' sedang diproses. Anda akan mendapat notifikasi ketika siap.',
                'export_log_id' => $exportLog->id,
            ]);
        }

        return redirect()->back()->with('success', 'Export ' . $exportLog->type_label . ' sedang diproses. Cek notifikasi (bell icon) untuk download.');
    }
}
