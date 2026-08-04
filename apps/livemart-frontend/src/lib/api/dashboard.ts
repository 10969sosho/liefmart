import apiClient from './client';
import type { DashboardStats, ApiResponse } from '@/types';

export const dashboardApi = {
  async getStats(): Promise<ApiResponse<DashboardStats>> {
    const response = await apiClient.get('/dashboard/stats');
    return response.data;
  },

  async getChartData(params?: {
    start_date?: string;
    end_date?: string;
    platform?: string;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/dashboard/chart-data', { params });
    return response.data;
  },

  async getRecentTransactions(limit: number = 10): Promise<ApiResponse<any[]>> {
    const response = await apiClient.get('/dashboard/recent-transactions', {
      params: { limit }
    });
    return response.data;
  },

  async getLowStockAlerts(threshold: number = 10): Promise<ApiResponse<any[]>> {
    const response = await apiClient.get('/dashboard/low-stock', {
      params: { threshold }
    });
    return response.data;
  },
};