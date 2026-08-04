import apiClient from './client';
import type { ReturPenjualan, ApiResponse, PaginatedResponse } from '@/types';

export const returApi = {
  // Get Retur Pembelian
  async getReturPembelian(params?: {
    page?: number;
    per_page?: number;
    start_date?: string;
    end_date?: string;
  }): Promise<PaginatedResponse<any>> {
    const response = await apiClient.get('/retur-pembelian', { params });
    return response.data;
  },

  // Create Retur Pembelian
  async createReturPembelian(data: any): Promise<ApiResponse<any>> {
    const response = await apiClient.post('/retur-pembelian/store', data);
    return response.data;
  },

  // Get Retur Pembelian by ID
  async getReturPembelianById(id: number): Promise<ApiResponse<any>> {
    const response = await apiClient.get(`/retur-pembelian/${id}`);
    return response.data;
  },

  // Search Orders for Return
  async searchOrders(params: {
    platform: string;
    order_number?: string;
    customer_name?: string;
  }): Promise<ApiResponse<any[]>> {
    const response = await apiClient.get('/retur-penjualan/search-orders', { params });
    return response.data;
  },

  // Get Retur Penjualan
  async getReturPenjualan(params?: {
    page?: number;
    per_page?: number;
    platform?: string;
    status?: string;
    start_date?: string;
    end_date?: string;
  }): Promise<PaginatedResponse<ReturPenjualan>> {
    const response = await apiClient.get('/retur-penjualan', { params });
    return response.data;
  },

  // Create Retur Penjualan
  async createReturPenjualan(data: {
    order_id: number;
    return_date: string;
    reason?: string;
    items: Array<{
      order_item_id: number;
      qty: number;
    }>;
  }): Promise<ApiResponse<ReturPenjualan>> {
    const response = await apiClient.post('/retur-penjualan/store', data);
    return response.data;
  },

  // Get Retur Penjualan by ID
  async getReturPenjualanById(id: number): Promise<ApiResponse<ReturPenjualan>> {
    const response = await apiClient.get(`/retur-penjualan/${id}`);
    return response.data;
  },

  // Process Retur Penjualan
  async processReturPenjualan(id: number): Promise<ApiResponse<ReturPenjualan>> {
    const response = await apiClient.put(`/retur-penjualan/${id}/process`);
    return response.data;
  },

  // Cancel Retur Penjualan
  async cancelReturPenjualan(id: number): Promise<ApiResponse<ReturPenjualan>> {
    const response = await apiClient.put(`/retur-penjualan/${id}/cancel`);
    return response.data;
  },

  // Reverse Retur Penjualan
  async reverseReturPenjualan(id: number): Promise<ApiResponse<ReturPenjualan>> {
    const response = await apiClient.put(`/retur-penjualan/${id}/reverse`);
    return response.data;
  },

  // Process Finance for Retur
  async processFinance(id: number, data: {
    refund_amount: number;
    refund_method: string;
    notes?: string;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.post(`/retur-penjualan/${id}/finance`, data);
    return response.data;
  },

  // Get Retur Offline
  async getReturOffline(params?: {
    page?: number;
    per_page?: number;
    start_date?: string;
    end_date?: string;
  }): Promise<PaginatedResponse<any>> {
    const response = await apiClient.get('/retur-offline', { params });
    return response.data;
  },

  // Create Retur Offline
  async createReturOffline(data: any): Promise<ApiResponse<any>> {
    const response = await apiClient.post('/retur-offline/store', data);
    return response.data;
  },
};
