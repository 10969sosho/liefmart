import apiClient from './client';
import type { 
  LoginCredentials, 
  RegisterData, 
  User, 
  ApiResponse 
} from '@/types';

export const authApi = {
  async login(credentials: LoginCredentials): Promise<ApiResponse<{ user: User; token: string }>> {
    const response = await apiClient.post('/auth/login', credentials);
    if (response.data.success && response.data.data.token) {
      localStorage.setItem('auth_token', response.data.data.token);
    }
    return response.data;
  },

  async register(data: RegisterData): Promise<ApiResponse<{ user: User; token: string }>> {
    const response = await apiClient.post('/auth/register', data);
    return response.data;
  },

  async logout(): Promise<ApiResponse<null>> {
    const response = await apiClient.post('/auth/logout');
    return response.data;
  },

  async getCurrentUser(): Promise<ApiResponse<User>> {
    const response = await apiClient.get('/auth/user');
    return response.data;
  },

  async forgotPassword(email: string): Promise<ApiResponse<null>> {
    const response = await apiClient.post('/auth/forgot-password', { email });
    return response.data;
  },

  async resetPassword(data: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<ApiResponse<null>> {
    const response = await apiClient.post('/auth/reset-password', data);
    return response.data;
  },

  async verifyEmail(token: string): Promise<ApiResponse<null>> {
    const response = await apiClient.post('/auth/verify-email', { token });
    return response.data;
  },
};