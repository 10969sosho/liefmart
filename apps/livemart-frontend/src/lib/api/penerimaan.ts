import apiClient from './client';
import type { Penerimaan, PenerimaanDetail, PaginatedResponse, ApiResponse } from '@/types';

export const penerimaanApi = {
  // Get all penerimaan
  async getAll(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    start_date?: string;
    end_date?: string;
  }): Promise<PaginatedResponse<Penerimaan>> {
    const response = await apiClient.get('/penerimaan', { params });
    return response.data;
  },

  // Get single penerimaan
  async getById(id: number): Promise<ApiResponse<Penerimaan>> {
    const response = await apiClient.get(`/penerimaan/${id}`);
    return response.data;
  },

  // Create penerimaan header
  async createHeader(data: {
    date: string;
    supplier_name?: string;
  }): Promise<ApiResponse<Penerimaan>> {
    const response = await apiClient.post('/penerimaan/create-header', data);
    return response.data;
  },

  // Store batch details
  async storeBatchDetails(id: number, details: Partial<PenerimaanDetail>[]): Promise<ApiResponse<Penerimaan>> {
    const response = await apiClient.post(`/penerimaan/${id}/store-batch-details`, { details });
    return response.data;
  },

  // Finalize penerimaan
  async finalize(id: number): Promise<ApiResponse<Penerimaan>> {
    const response = await apiClient.post(`/penerimaan/${id}/finalize`);
    return response.data;
  },

  // Update penerimaan
  async update(id: number, data: Partial<Penerimaan>): Promise<ApiResponse<Penerimaan>> {
    const response = await apiClient.put(`/penerimaan/${id}`, data);
    return response.data;
  },

  // Delete penerimaan
  async delete(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/penerimaan/${id}`);
    return response.data;
  },

  // Export penerimaan
  async export(params?: {
    start_date?: string;
    end_date?: string;
    format?: 'excel' | 'pdf';
  }): Promise<Blob> {
    const response = await apiClient.get('/penerimaan/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  },

  // Get price history
  async getPriceHistory(productId: number): Promise<ApiResponse<any[]>> {
    const response = await apiClient.get(`/penerimaan/price-history/${productId}`);
    return response.data;
  },
};
