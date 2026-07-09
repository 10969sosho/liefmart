<?php

namespace App\Notifications;

use App\Models\ExportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public ExportLog $exportLog)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'export_log_id' => $this->exportLog->id,
            'type' => $this->exportLog->type,
            'type_label' => $this->exportLog->type_label,
            'status' => $this->exportLog->status,
            'file_name' => $this->exportLog->file_name,
            'error_message' => $this->exportLog->error_message,
            'completed_at' => $this->exportLog->completed_at?->toDateTimeString(),
            'message' => $this->exportLog->status === 'completed'
                ? "Export {$this->exportLog->type_label} siap diunduh"
                : "Export {$this->exportLog->type_label} gagal: {$this->exportLog->error_message}",
        ];
    }
}
