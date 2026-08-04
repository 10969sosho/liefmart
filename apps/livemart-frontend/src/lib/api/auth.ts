import apiClient from './client';
import axios from 'axios';
import type { 
  LoginCredentials, 
  RegisterData, 
  User, 
  ApiResponse 
} from '@/types';

export const authApi = {
  // Get CSRF cookie (required for Sanctum)
  async getCsrfCookie() {
    const sanctumUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
    return axios.get(`${sanctumUrl}/sanctum/csrf-cookie`, {
      withCredentials: true,
    });
  },

  // Login
  async login(credentials: LoginCredentials): Promise<ApiResponse<{ user: User; token: string }>> {
    const response = await apiClient.post('/auth/login', credentials);
    return response.data;
  },

  // Register
  async register(data: RegisterData): Promise<ApiResponse<{ user: User; token: string }>> {
    const response = await apiClient.post('/auth/register', data);
    return response.data;
  },

  // Logout
  async logout(): Promise<ApiResponse<null>> {
    const response = await apiClient.post('/auth/logout');
    return response.data;
  },

  // Get current user
  async getCurrentUser(): Promise<ApiResponse<User>> {
    const response = await apiClient.get('/auth/user');
    return response.data;
  },

  // Forgot password
  async forgotPassword(email: string): Promise<ApiResponse<null>> {
    const response = await apiClient.post('/auth/forgot-password', { email });
    return response.data;
  },

  // Reset password
  async resetPassword(data: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<ApiResponse<null>> {
    const response = await apiClient.post('/auth/reset-password', data);
    return response.data;
  },

  // Verify email
  async verifyEmail(token: string): Promise<ApiResponse<null>> {
    const response = await apiClient.post('/auth/verify-email', { token });
    return response.data;
  },
};
