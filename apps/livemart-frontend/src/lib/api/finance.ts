import apiClient from './client';
import type { FinanceTransaction, PaginatedResponse, ApiResponse } from '@/types';

export const financeApi = {
  // Get finance transactions by platform
  async getByPlatform(
    platform: 'shopee' | 'shopee2' | 'tiktok' | 'tiktok2' | 'offline',
    params?: {
      page?: number;
      per_page?: number;
      start_date?: string;
      end_date?: string;
      status?: string;
    }
  ): Promise<PaginatedResponse<FinanceTransaction>> {
    const response = await apiClient.get(`/finance/${platform}`, { params });
    return response.data;
  },

  // Preview import
  async previewImport(platform: string, file: File): Promise<ApiResponse<any>> {
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await apiClient.post(`/finance/${platform}/import/preview`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  // Process import
  async processImport(platform: string, data: any): Promise<ApiResponse<any>> {
    const response = await apiClient.post(`/finance/${platform}/import/process`, data);
    return response.data;
  },

  // Manual store
  async manualStore(platform: string, data: {
    order_id: number;
    amount: number;
    payment_date: string;
  }): Promise<ApiResponse<FinanceTransaction>> {
    const response = await apiClient.post(`/finance/${platform}/manual-store`, data);
    return response.data;
  },

  // Delete transaction
  async delete(platform: string, id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/finance/${platform}/${id}`);
    return response.data;
  },

  // Adjust transaction
  async adjust(platform: string, id: number, data: {
    amount: number;
    reason?: string;
  }): Promise<ApiResponse<FinanceTransaction>> {
    const response = await apiClient.post(`/finance/${platform}/adjust/${id}`, data);
    return response.data;
  },

  // Lock transaction
  async lock(platform: string, id: number): Promise<ApiResponse<FinanceTransaction>> {
    const response = await apiClient.post(`/finance/${platform}/lock/${id}`);
    return response.data;
  },

  // Unlock transaction
  async unlock(platform: string, id: number): Promise<ApiResponse<FinanceTransaction>> {
    const response = await apiClient.post(`/finance/${platform}/unlock/${id}`);
    return response.data;
  },

  // Print invoice
  async printInvoice(platform: string, id: number): Promise<Blob> {
    const response = await apiClient.get(`/finance/${platform}/print-invoice/${id}`, {
      responseType: 'blob',
    });
    return response.data;
  },

  // Get history
  async getHistory(platform: string, id: number): Promise<ApiResponse<any[]>> {
    const response = await apiClient.get(`/finance/${platform}/history/${id}`);
    return response.data;
  },

  // Export
  async export(platform: string, format: 'excel' | 'pdf' = 'excel', params?: {
    start_date?: string;
    end_date?: string;
  }): Promise<Blob> {
    const response = await apiClient.get(`/finance/${platform}/export/${format}`, {
      params,
      responseType: 'blob',
    });
    return response.data;
  },

  // Get unpaid orders
  async getUnpaidOrders(platform?: string): Promise<ApiResponse<any[]>> {
    const response = await apiClient.get('/finance/unpaid-orders', {
      params: { platform },
    });
    return response.data;
  },
};
