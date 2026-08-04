import apiClient from './client';
import type { WarehouseStock, PaginatedResponse, ApiResponse } from '@/types';

export const warehouseApi = {
  // Get warehouse stock
  async getStock(params?: {
    page?: number;
    per_page?: number;
    location?: 'unlocated' | 'warehouse_a';
    search?: string;
  }): Promise<PaginatedResponse<WarehouseStock>> {
    const response = await apiClient.get('/warehouse/stock', { params });
    return response.data;
  },

  // Move stock
  async moveStock(data: {
    product_id: number;
    qty: number;
    from_location: string;
    to_location: string;
  }): Promise<ApiResponse<WarehouseStock>> {
    const response = await apiClient.post('/warehouse/move', data);
    return response.data;
  },

  // Get stock analytics
  async getAnalytics(params?: {
    start_date?: string;
    end_date?: string;
  }): Promise<ApiResponse<any>> {
    const response = await apiClient.get('/warehouse/stock/analytics', { params });
    return response.data;
  },

  // Export stock
  async exportStock(format: 'excel' | 'csv' = 'excel'): Promise<Blob> {
    const response = await apiClient.get(`/warehouse/stock/export`, {
      params: { format },
      responseType: 'blob',
    });
    return response.data;
  },

  // Export selected items
  async exportSelectedItems(productIds: number[]): Promise<Blob> {
    const response = await apiClient.post('/warehouse/stock/export-selected', {
      product_ids: productIds,
    }, {
      responseType: 'blob',
    });
    return response.data;
  },
};
