import apiClient from './client';

export const penerimaanApi = {
  // Get all penerimaan
  async getAll(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    start_date?: string;
    end_date?: string;
  }) {
    const response = await apiClient.get('/penerimaan', { params });
    return response.data;
  },

  // Get single penerimaan
  async getById(id: number) {
    const response = await apiClient.get(`/penerimaan/${id}`);
    return response.data;
  },

  // Create penerimaan header
  async createHeader(data: {
    main_category_id: number;
    tax_category_id: number;
    nomor_po: string;
    tanggal_penerimaan: string;
    metode_pembayaran: 'Cash' | 'Jatuh Tempo';
    tanggal_jatuh_tempo?: string;
    catatan?: string;
  }) {
    const response = await apiClient.post('/penerimaan/create-header', data);
    return response.data;
  },

  // Store batch details
  async storeBatchDetails(id: number, items: {
    product_id: number;
    qty: number;
    satuan_id: number;
    harga_hpp: number;
    diskon_persen_1?: number;
    diskon_nominal_1?: number;
    diskon_persen_2?: number;
    diskon_nominal_2?: number;
    diskon_persen_3?: number;
    diskon_nominal_3?: number;
    diskon_persen_4?: number;
    diskon_nominal_4?: number;
    diskon_persen_5?: number;
    diskon_nominal_5?: number;
    is_free?: boolean;
    catatan?: string;
  }[]) {
    const response = await apiClient.post(`/penerimaan/${id}/store-batch-details`, { items });
    return response.data;
  },

  // Delete penerimaan
  async delete(id: number) {
    const response = await apiClient.delete(`/penerimaan/${id}`);
    return response.data;
  },

  // Get tax categories
  async getTaxCategories(mainCategoryId: number) {
    const response = await apiClient.get('/penerimaan/tax-categories', {
      params: { main_category_id: mainCategoryId }
    });
    return response.data;
  },

  // Get products
  async getProducts(params?: {
    main_category_id?: number;
    search?: string;
  }) {
    const response = await apiClient.get('/penerimaan/products', { params });
    return response.data;
  },

  // Get satuans
  async getSatuans() {
    const response = await apiClient.get('/penerimaan/satuans');
    return response.data;
  },

  // Get main categories
  async getMainCategories() {
    const response = await apiClient.get('/penerimaan/main-categories');
    return response.data;
  },

  // Get price history
  async getPriceHistory(productId: number) {
    const response = await apiClient.get(`/penerimaan/price-history/${productId}`);
    return response.data;
  },
};
