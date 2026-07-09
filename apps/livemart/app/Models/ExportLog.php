<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportLog extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'filters',
        'status',
        'file_path',
        'file_name',
        'file_size',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(string $filePath, string $fileName, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'gross_profit_offline' => 'Gross Profit Offline',
            'gross_profit_report' => 'Gross Profit Report',
            'sales_by_platform' => 'Sales by Platform',
            'sales_by_platform_product' => 'Sales by Platform Product',
            'sales_by_master_product' => 'Sales by Master Product',
            'sales_by_master_product_special' => 'Sales by Master Product Special',
            'sales_by_status_day' => 'Sales by Status Day',
            'sales_by_day_of_week' => 'Sales by Day of Week',
            'sales_by_date_number' => 'Sales by Date Number',
            'sales_detail_report' => 'Sales Detail Report',
            'sales_value_report' => 'Sales Value Report',
            'sales_volume_report' => 'Sales Volume Report',
            'monthly_sales_summary' => 'Monthly Sales Summary',
            'daily_sales_report' => 'Daily Sales Report',
            'discount_analysis_report' => 'Discount Analysis Report',
            'single_item_report' => 'Single Item Report',
            'multiple_item_report' => 'Multiple Item Report',
            'internal_product_sales' => 'Internal Product Sales',
            'sales_export_mapped' => 'Sales Export Mapped',
            'produk_platform_terlaris' => 'Produk Platform Terlaris',
            'produk_internal_terlaris' => 'Produk Internal Terlaris',
            'offline_monthly_sales_summary' => 'Offline Monthly Sales Summary',
            'offline_sales_by_customer' => 'Offline Sales by Customer',
            'offline_sales_by_product' => 'Offline Sales by Product',
            'offline_sales_detail_report' => 'Offline Sales Detail Report',
            'finance_shopee' => 'Finance Shopee',
            'finance_shopee2' => 'Finance Shopee2',
            'finance_tiktok' => 'Finance TikTok',
            'finance_tiktok2' => 'Finance TikTok2',
        ];

        return $labels[$this->type] ?? ucwords(str_replace('_', ' ', $this->type));
    }
}
