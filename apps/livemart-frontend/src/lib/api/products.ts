import apiClient from './client';
import type { Product, PaginatedResponse, ApiResponse } from '@/types';

export const productsApi = {
  // Get all products
  async getAll(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    brand_id?: number;
  }): Promise<PaginatedResponse<Product>> {
    const response = await apiClient.get('/products', { params });
    return response.data;
  },

  // Get single product
  async getById(id: number): Promise<ApiResponse<Product>> {
    const response = await apiClient.get(`/products/${id}`);
    return response.data;
  },

  // Create product
  async create(data: Partial<Product>): Promise<ApiResponse<Product>> {
    const response = await apiClient.post('/products', data);
    return response.data;
  },

  // Update product
  async update(id: number, data: Partial<Product>): Promise<ApiResponse<Product>> {
    const response = await apiClient.put(`/products/${id}`, data);
    return response.data;
  },

  // Delete product
  async delete(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/products/${id}`);
    return response.data;
  },

  // Export products
  async export(format: 'excel' | 'csv' | 'pdf' = 'excel'): Promise<Blob> {
    const response = await apiClient.get(`/products/export/${format}`, {
      responseType: 'blob',
    });
    return response.data;
  },
};
