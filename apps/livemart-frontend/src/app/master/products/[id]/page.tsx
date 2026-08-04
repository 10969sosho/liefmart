'use client';

import * as React from 'react';
import { useRouter, useParams } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { ArrowLeft, Loader2, Package } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { productsApi } from '@/lib/api';
import { formatCurrency } from '@/lib/utils';

const queryClient = new QueryClient({
  defaultOptions: { queries: { refetchOnWindowFocus: false, retry: 1 } },
});

function ProductDetailContent() {
  const router = useRouter();
  const params = useParams();
  const productId = Number(params.id);

  const { data: product, isLoading } = useQuery({
    queryKey: ['product', productId],
    queryFn: async () => {
      const response = await productsApi.getById(productId);
      return response.data;
    },
  });

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Navbar />
        <div className="flex">
          <Sidebar />
          <main className="flex-1 p-8 flex items-center justify-center">
            <Loader2 className="w-8 h-8 animate-spin text-gray-400" />
          </main>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Navbar />
        <div className="flex">
          <Sidebar />
          <main className="flex-1 p-8">
            <div className="text-center">
              <h2 className="text-xl font-semibold text-gray-900">Produk tidak ditemukan</h2>
              <button
                onClick={() => router.push('/master/products')}
                className="mt-4 text-blue-600 hover:text-blue-700"
              >
                Kembali ke Products
              </button>
            </div>
          </main>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-8">
          <div className="max-w-3xl mx-auto">
            <div className="mb-6">
              <button
                onClick={() => router.push('/master/products')}
                className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 mb-4"
              >
                <ArrowLeft className="w-4 h-4" />
                Kembali ke Products
              </button>
              <h1 className="text-3xl font-bold text-gray-900">Detail Produk</h1>
            </div>

            <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
              <div className="p-6 border-b border-gray-200">
                <div className="flex items-center gap-4">
                  <div className="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                    <Package className="w-8 h-8 text-gray-400" />
                  </div>
                  <div>
                    <h2 className="text-2xl font-bold text-gray-900">{product.name}</h2>
                    <p className="text-sm text-gray-500">ID: {product.id}</p>
                  </div>
                </div>
              </div>

              <div className="p-6">
                <div className="grid grid-cols-2 gap-6">
                  <div>
                    <h3 className="text-sm font-medium text-gray-500 mb-1">SKU</h3>
                    <p className="text-lg font-semibold text-gray-900">{product.sku || '-'}</p>
                  </div>
                  <div>
                    <h3 className="text-sm font-medium text-gray-500 mb-1">Brand</h3>
                    <p className="text-lg font-semibold text-gray-900">{product.brand?.name || '-'}</p>
                  </div>
                  <div>
                    <h3 className="text-sm font-medium text-gray-500 mb-1">Harga Awal</h3>
                    <p className="text-lg font-semibold text-gray-900">
                      {product.initial_price ? formatCurrency(product.initial_price) : '-'}
                    </p>
                  </div>
                  <div>
                    <h3 className="text-sm font-medium text-gray-500 mb-1">Stock</h3>
                    <p className="text-lg font-semibold">
                      <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
                        (product.stock || 0) < 10 
                          ? 'bg-red-100 text-red-800' 
                          : 'bg-green-100 text-green-800'
                      }`}>
                        {product.stock || 0} pcs
                      </span>
                    </p>
                  </div>
                  <div>
                    <h3 className="text-sm font-medium text-gray-500 mb-1">Category</h3>
                    <p className="text-lg font-semibold text-gray-900">{product.product_category?.name || '-'}</p>
                  </div>
                  <div>
                    <h3 className="text-sm font-medium text-gray-500 mb-1">Created At</h3>
                    <p className="text-lg font-semibold text-gray-900">
                      {product.created_at ? new Date(product.created_at).toLocaleDateString('id-ID') : '-'}
                    </p>
                  </div>
                </div>
              </div>

              <div className="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <button
                  onClick={() => router.push(`/master/products/${product.id}/edit`)}
                  className="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700"
                >
                  Edit Produk
                </button>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}

export default function ProductDetailPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <ProductDetailContent />
    </QueryClientProvider>
  );
}