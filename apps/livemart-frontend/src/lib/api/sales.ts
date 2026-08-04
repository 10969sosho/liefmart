import apiClient from './client';
import type { Order, OrderItem, PaginatedResponse, ApiResponse } from '@/types';

export const salesApi = {
  // Get all orders
  async getOrders(params?: {
    page?: number;
    per_page?: number;
    platform?: string;
    status?: string;
    start_date?: string;
    end_date?: string;
    search?: string;
  }): Promise<PaginatedResponse<Order>> {
    const response = await apiClient.get('/sales/orders', { params });
    return response.data;
  },

  // Get order by ID
  async getOrderById(id: number): Promise<ApiResponse<Order>> {
    const response = await apiClient.get(`/sales/orders/${id}`);
    return response.data;
  },

  // Create offline sale
  async createOfflineSale(data: {
    customer_id?: number;
    order_date: string;
    items: Array<{
      product_id: number;
      qty: number;
      price: number;
    }>;
  }): Promise<ApiResponse<Order>> {
    const response = await apiClient.post('/sales/offline/store', data);
    return response.data;
  },

  // Delete order
  async deleteOrder(id: number, platform: string): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/sales/${platform}/${id}`);
    return response.data;
  },

  // Preview import
  async previewImport(platform: string, file: File): Promise<ApiResponse<any>> {
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await apiClient.post(`/sales/${platform}/preview-import`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  // Process import
  async processImport(platform: string, data: any): Promise<ApiResponse<any>> {
    const response = await apiClient.post(`/sales/${platform}/process-import`, data);
    return response.data;
  },

  // Generate SJ number
  async generateSJNumber(): Promise<ApiResponse<{ sj_number: string }>> {
    const response = await apiClient.post('/sales/generate-sj-number');
    return response.data;
  },

  // Check duplicate order
  async checkDuplicateOrder(orderNumber: string, platform: string): Promise<ApiResponse<{ exists: boolean }>> {
    const response = await apiClient.post('/sales/check-order', {
      order_number: orderNumber,
      platform,
    });
    return response.data;
  },

  // Get product stock info
  async getProductStockInfo(productId: number): Promise<ApiResponse<any>> {
    const response = await apiClient.get(`/products/${productId}/stock-info`);
    return response.data;
  },

  // Store online manual
  async storeOnlineManual(platform: string, data: any): Promise<ApiResponse<Order>> {
    const response = await apiClient.post(`/sales/online/store`, {
      platform,
      ...data,
    });
    return response.data;
  },
};
