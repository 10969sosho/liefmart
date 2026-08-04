// User Types
export interface User {
  id: number;
  name: string;
  email: string;
  role?: Role;
  permissions?: Permission[];
  created_at?: string;
  updated_at?: string;
}

export interface Role {
  id: number;
  name: string;
  permissions?: Permission[];
}

export interface Permission {
  id: number;
  name: string;
  description?: string;
}

// Auth Types
export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

// Dashboard Types
export interface DashboardStats {
  total_sales: number;
  total_orders: number;
  total_customers: number;
  total_products: number;
  sales_by_platform: PlatformSales[];
  recent_transactions: Transaction[];
  low_stock_products: Product[];
}

export interface PlatformSales {
  platform: string;
  total_sales: number;
  total_orders: number;
  growth_percentage: number;
}

export interface Transaction {
  id: number;
  order_number: string;
  platform: string;
  customer_name: string;
  total_amount: number;
  status: string;
  created_at: string;
}

// Product Types
export interface Product {
  id: number;
  name: string;
  sku?: string;
  barcode?: string;
  brand_id?: number;
  brand?: Brand;
  sub_brand_id?: number;
  sub_brand?: SubBrand;
  product_category_id?: number;
  product_category?: ProductCategory;
  product_type_id?: number;
  product_type?: ProductType;
  product_size_id?: number;
  product_size?: ProductSize;
  product_variant_id?: number;
  product_variant?: ProductVariant;
  initial_price?: number;
  stock?: number;
  created_at?: string;
  updated_at?: string;
}

export interface Brand {
  id: number;
  name: string;
}

export interface SubBrand {
  id: number;
  name: string;
  brand_id: number;
}

export interface ProductCategory {
  id: number;
  name: string;
  sub_brand_id: number;
}

export interface ProductType {
  id: number;
  name: string;
  product_category_id: number;
}

export interface ProductSize {
  id: number;
  name: string;
  product_type_id: number;
}

export interface ProductVariant {
  id: number;
  name: string;
  product_size_id: number;
}

// Customer Types
export interface Customer {
  id: number;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  tax_id?: string;
  is_pkp: boolean;
  created_at?: string;
  updated_at?: string;
}

// Order Types
export interface Order {
  id: number;
  order_number: string;
  platform: 'shopee' | 'shopee2' | 'tiktok' | 'tiktok2' | 'offline';
  customer_id?: number;
  customer?: Customer;
  order_date: string;
  total_amount: number;
  status: 'pending' | 'processing' | 'completed' | 'cancelled';
  payment_status: 'unpaid' | 'paid' | 'refunded';
  items: OrderItem[];
  created_at: string;
  updated_at: string;
}

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  product?: Product;
  platform_product_name?: string;
  qty: number;
  price: number;
  subtotal: number;
}

// Penerimaan Types
export interface Penerimaan {
  id: number;
  penerimaan_number: string;
  date: string;
  supplier_name?: string;
  total_items: number;
  total_amount: number;
  status: 'draft' | 'finalized';
  details?: PenerimaanDetail[];
  created_at: string;
  updated_at: string;
}

export interface PenerimaanDetail {
  id: number;
  penerimaan_id: number;
  product_id: number;
  product?: Product;
  qty: number;
  price: number;
  tax_category_id?: number;
  subtotal: number;
}

// Warehouse Types
export interface WarehouseStock {
  id: number;
  product_id: number;
  product?: Product;
  location: 'unlocated' | 'warehouse_a';
  qty: number;
  damaged_qty?: number;
  created_at: string;
  updated_at: string;
}

// Finance Types
export interface FinanceTransaction {
  id: number;
  transaction_number: string;
  platform: 'shopee' | 'shopee2' | 'tiktok' | 'tiktok2' | 'offline';
  order_id?: number;
  order?: Order;
  amount: number;
  payment_date: string;
  status: 'pending' | 'paid' | 'locked';
  invoice_number?: string;
  is_locked: boolean;
  created_at: string;
  updated_at: string;
}

// Analytics Types
export interface SalesReport {
  date: string;
  platform: string;
  total_sales: number;
  total_orders: number;
  avg_order_value: number;
}

export interface GrossProfitReport {
  product_name: string;
  qty_sold: number;
  revenue: number;
  cost: number;
  gross_profit: number;
  margin_percentage: number;
}

// Retur Types
export interface ReturPenjualan {
  id: number;
  retur_number: string;
  order_id: number;
  order?: Order;
  return_date: string;
  total_amount: number;
  reason?: string;
  status: 'pending' | 'processed' | 'cancelled';
  details?: ReturPenjualanDetail[];
  created_at: string;
  updated_at: string;
}

export interface ReturPenjualanDetail {
  id: number;
  retur_penjualan_id: number;
  order_item_id: number;
  qty: number;
  price: number;
  subtotal: number;
}

// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
