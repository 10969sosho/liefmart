import apiClient from './client';
import type { ApiResponse } from '@/types';

export const analyticsApi = {
  // Sales Value Report
  async getSalesValue(params: {
    start_date: string;
    end_date: string;
    platform?: string;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/sales-value', { params });
    return response.data;
  },

  // Sales Volume Report
  async getSalesVolume(params: {
    start_date: string;
    end_date: string;
    platform?: string;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/sales-volume', { params });
    return response.data;
  },

  // Gross Profit Report
  async getGrossProfit(params: {
    start_date: string;
    end_date: string;
    platform?: string;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/gross-profit', { params });
    return response.data;
  },

  // Monthly Sales Summary
  async getMonthlySummary(params: {
    year: number;
    month: number;
    platform?: string;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/monthly-summary', { params });
    return response.data;
  },

  // Sales by Platform
  async getSalesByPlatform(params: {
    start_date: string;
    end_date: string;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/sales-by-platform', { params });
    return response.data;
  },

  // Sales Detail Report
  async getSalesDetail(params: {
    start_date: string;
    end_date: string;
    platform?: string;
    product_id?: number;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/sales-detail', { params });
    return response.data;
  },

  // Offline Sales Detail
  async getOfflineSalesDetail(params: {
    start_date: string;
    end_date: string;
    customer_id?: number;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/offline/sales-detail', { params });
    return response.data;
  },

  // Offline Monthly Summary
  async getOfflineMonthlySummary(params: {
    year: number;
    month: number;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/offline/monthly-summary', { params });
    return response.data;
  },

  // Offline Sales by Customer
  async getOfflineSalesByCustomer(params: {
    start_date: string;
    end_date: string;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/offline/sales-by-customer', { params });
    return response.data;
  },

  // Finance Analytics
  async getFinanceAnalytics(
    platform: 'shopee' | 'shopee2' | 'tiktok' | 'tiktok2',
    params: {
      start_date: string;
      end_date: string;
    }
  ): Promise<ApiResponse<any>> {
    const response = await apiClient.get(`/analytics/finance/${platform}`, { params });
    return response.data;
  },

  // Dispatch Export Job
  async dispatchExport(data: {
    report_type: string;
    params: any;
    format: 'excel' | 'pdf';
  }): Promise<ApiResponse<{ export_id: number }>> {
    const response = await apiClient.post('/analytics/exports/dispatch', data);
    return response.data;
  },

  // List Exports
  async listExports(params?: {
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/analytics/exports/list', { params });
    return response.data;
  },

  // Download Export
  async downloadExport(exportId: number): Promise<Blob> {
    const response = await apiClient.get(`/analytics/exports/${exportId}/download`, {
      responseType: 'blob',
    });
    return response.data;
  },

  // Get Export Notifications
  async getExportNotifications(): Promise<ApiResponse<any[]>> {
    const response = await apiClient.get('/analytics/exports/notifications');
    return response.data;
  },
};
