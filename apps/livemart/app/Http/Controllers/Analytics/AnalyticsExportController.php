<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAnalyticsExport;
use App\Models\ExportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnalyticsExportController extends Controller
{
    /**
     * Dispatch a new analytics export job.
     */
    public function export(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'filters' => 'nullable|array',
        ]);

        $filters = $request->input('filters', []);

        // Create export log
        $exportLog = ExportLog::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'filters' => $filters,
            'status' => 'pending',
        ]);

        // Dispatch job
        ProcessAnalyticsExport::dispatch(
            $request->type,
            $filters,
            auth()->id(),
            $exportLog->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Export sedang diproses. Anda akan mendapat notifikasi ketika export siap.',
                'export_log_id' => $exportLog->id,
            ]);
        }

        return redirect()->back()->with('success', 'Export sedang diproses. Cek notifikasi untuk download.');
    }

    /**
     * Get export status as JSON.
     */
    public function status($id)
    {
        $exportLog = ExportLog::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'id' => $exportLog->id,
            'type' => $exportLog->type,
            'type_label' => $exportLog->type_label,
            'status' => $exportLog->status,
            'file_name' => $exportLog->file_name,
            'file_size' => $exportLog->file_size,
            'error_message' => $exportLog->error_message,
            'created_at' => $exportLog->created_at->toDateTimeString(),
            'completed_at' => $exportLog->completed_at?->toDateTimeString(),
        ]);
    }

    /**
     * Download completed export file.
     */
    public function download($id)
    {
        $exportLog = ExportLog::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->firstOrFail();

        $disk = Storage::disk('local');

        if (!$disk->exists($exportLog->file_path)) {
            abort(404, 'File export tidak ditemukan. Mungkin sudah kadaluarsa.');
        }

        $fullPath = $disk->path($exportLog->file_path);

        return response()->download(
            $fullPath,
            $exportLog->file_name,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(false);
    }

    /**
     * List recent exports for the current user.
     */
    public function listExports()
    {
        $exports = ExportLog::forUser(auth()->id())
            ->latest()
            ->paginate(20);

        if (request()->wantsJson()) {
            return response()->json($exports);
        }

        return view('analytics.exports.index', compact('exports'));
    }

    /**
     * Get unread export notifications count and list.
     */
    public function notifications()
    {
        $user = auth()->user();
        $notifications = $user->notifications()
            ->where('type', 'App\Notifications\ExportReadyNotification')
            ->latest()
            ->take(10)
            ->get();

        $unreadCount = $user->unreadNotifications()
            ->where('type', 'App\Notifications\ExportReadyNotification')
            ->count();

        $items = $notifications->map(function ($notification) {
            $data = $notification->data;
            return [
                'id' => $notification->id,
                'message' => $data['message'] ?? '',
                'type' => $data['type'] ?? '',
                'type_label' => $data['type_label'] ?? '',
                'status' => $data['status'] ?? '',
                'export_log_id' => $data['export_log_id'] ?? null,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $items,
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications()
            ->where('type', 'App\Notifications\ExportReadyNotification')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Dispatch export from existing controller export methods.
     * This is a helper method that other controllers can call.
     */
    public static function dispatchExport(string $type, array $filters, int $userId): ExportLog
    {
        $exportLog = ExportLog::create([
            'user_id' => $userId,
            'type' => $type,
            'filters' => $filters,
            'status' => 'pending',
        ]);

        ProcessAnalyticsExport::dispatch(
            $type,
            $filters,
            $userId,
            $exportLog->id
        );

        return $exportLog;
    }
}
