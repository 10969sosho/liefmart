import apiClient from './client';
import type { User, Role, Permission, PaginatedResponse, ApiResponse } from '@/types';

export const adminApi = {
  // Users
  async getUsers(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    role?: string;
  }): Promise<PaginatedResponse<User>> {
    const response = await apiClient.get('/admin/users', { params });
    return response.data;
  },

  async createUser(data: {
    name: string;
    email: string;
    password: string;
    role_id?: number;
  }): Promise<ApiResponse<User>> {
    const response = await apiClient.post('/admin/users', data);
    return response.data;
  },

  async updateUser(id: number, data: Partial<User>): Promise<ApiResponse<User>> {
    const response = await apiClient.put(`/admin/users/${id}`, data);
    return response.data;
  },

  async deleteUser(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/admin/users/${id}`);
    return response.data;
  },

  async toggleUserStatus(id: number): Promise<ApiResponse<User>> {
    const response = await apiClient.post(`/admin/users/${id}/toggle-status`);
    return response.data;
  },

  // Roles
  async getRoles(params?: {
    page?: number;
    per_page?: number;
  }): Promise<PaginatedResponse<Role>> {
    const response = await apiClient.get('/admin/roles', { params });
    return response.data;
  },

  async createRole(data: {
    name: string;
    permissions?: number[];
  }): Promise<ApiResponse<Role>> {
    const response = await apiClient.post('/admin/roles', data);
    return response.data;
  },

  async updateRole(id: number, data: {
    name?: string;
    permissions?: number[];
  }): Promise<ApiResponse<Role>> {
    const response = await apiClient.put(`/admin/roles/${id}`, data);
    return response.data;
  },

  async deleteRole(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/admin/roles/${id}`);
    return response.data;
  },

  // Permissions
  async getPermissions(): Promise<ApiResponse<Permission[]>> {
    const response = await apiClient.get('/admin/permissions');
    return response.data;
  },

  // Profile
  async updateProfile(data: {
    name?: string;
    email?: string;
    password?: string;
    current_password?: string;
  }): Promise<ApiResponse<User>> {
    const response = await apiClient.put('/profile', data);
    return response.data;
  },

  // Database Restore
  async restoreDatabase(file: File): Promise<ApiResponse<any>> {
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await apiClient.post('/database-restore', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  // Chunked Upload
  async uploadChunk(chunk: Blob, chunkIndex: number, totalChunks: number, fileName: string): Promise<ApiResponse<any>> {
    const formData = new FormData();
    formData.append('chunk', chunk);
    formData.append('chunkIndex', chunkIndex.toString());
    formData.append('totalChunks', totalChunks.toString());
    formData.append('fileName', fileName);
    
    const response = await apiClient.post('/chunked-upload/chunk', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  async mergeChunks(fileName: string, totalChunks: number): Promise<ApiResponse<any>> {
    const response = await apiClient.post('/chunked-upload/merge', {
      fileName,
      totalChunks,
    });
    return response.data;
  },
};
