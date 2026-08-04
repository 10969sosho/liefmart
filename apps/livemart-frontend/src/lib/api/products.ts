import apiClient from './client';
import type { Product, PaginatedResponse, ApiResponse } from '@/types';

export const productsApi = {
  // Get all products - maps the legacy API response
  async getAll(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    brand_id?: number;
  }): Promise<PaginatedResponse<Product>> {
    const response = await apiClient.get('/products', { params });
    
    // Handle both paginated and array responses
    if (Array.isArray(response.data)) {
      return {
        data: response.data.map((p: any) => ({
          id: p.id,
          name: p.text || p.name,
          sku: p.sku || '',
          stock: p.stock || 0,
          initial_price: p.harga_hpp || p.initial_price || 0,
        })),
        current_page: 1,
        last_page: 1,
        per_page: response.data.length,
        total: response.data.length,
      };
    }
    
    return {
      data: (response.data.data || []).map((p: any) => ({
        id: p.id,
        name: p.text || p.name,
        sku: p.sku || '',
        stock: p.stock || 0,
        initial_price: p.harga_hpp || p.initial_price || 0,
      })),
      current_page: response.data.current_page || 1,
      last_page: response.data.last_page || 1,
      per_page: response.data.per_page || 20,
      total: response.data.total || 0,
    };
  },

  async getById(id: number): Promise<ApiResponse<Product>> {
    const response = await apiClient.get(`/products/${id}`);
    return response.data;
  },

  async create(data: Partial<Product>): Promise<ApiResponse<Product>> {
    const response = await apiClient.post('/products', data);
    return response.data;
  },

  async update(id: number, data: Partial<Product>): Promise<ApiResponse<Product>> {
    const response = await apiClient.put(`/products/${id}`, data);
    return response.data;
  },

  async delete(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/products/${id}`);
    return response.data;
  },

  async export(format: 'excel' | 'csv' | 'pdf' = 'excel'): Promise<Blob> {
    const response = await apiClient.get(`/products/export/${format}`, {
      responseType: 'blob',
    });
    return response.data;
  },
};