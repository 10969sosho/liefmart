'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { ArrowLeft, Package, TrendingUp, AlertTriangle, Loader2 } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { warehouseApi } from '@/lib/api';

const queryClient = new QueryClient({
  defaultOptions: { queries: { refetchOnWindowFocus: false, retry: 1 } },
});

function WarehouseContent() {
  const router = useRouter();

  const { data: unlocatedStock, isLoading } = useQuery({
    queryKey: ['warehouse-unlocated'],
    queryFn: async () => {
      const response = await warehouseApi.getStock({
        location: 'unlocated',
        per_page: 50,
      });
      return response;
    },
  });

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-8">
          <div className="max-w-7xl mx-auto space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <button
                  onClick={() => router.push('/')}
                  className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 mb-2"
                >
                  <ArrowLeft className="w-4 h-4" />
                  Kembali
                </button>
                <h1 className="text-3xl font-bold text-gray-900">Warehouse Management</h1>
                <p className="text-gray-500 mt-1">Kelola stok dan lokasi penyimpanan</p>
              </div>
              <button
                onClick={() => router.push('/warehouse/move')}
                className="btn-modern bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg"
              >
                Move to Warehouse A
              </button>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm card-hover">
                <div className="flex items-start justify-between mb-4">
                  <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center shadow-lg">
                    <Package className="w-6 h-6 text-white" />
                  </div>
                </div>
                <p className="text-sm font-medium text-gray-500 mb-1">Unlocated Items</p>
                <div className="text-3xl font-bold text-gray-900">
                  {unlocatedStock?.data?.length || 0}
                </div>
                <p className="text-xs text-gray-500 mt-1">Items belum dipindahkan</p>
              </div>

              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm card-hover">
                <div className="flex items-start justify-between mb-4">
                  <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center shadow-lg">
                    <TrendingUp className="w-6 h-6 text-white" />
                  </div>
                </div>
                <p className="text-sm font-medium text-gray-500 mb-1">Warehouse A</p>
                <div className="text-3xl font-bold text-gray-900">-</div>
                <p className="text-xs text-gray-500 mt-1">Items di warehouse</p>
              </div>

              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm card-hover">
                <div className="flex items-start justify-between mb-4">
                  <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center shadow-lg">
                    <AlertTriangle className="w-6 h-6 text-white" />
                  </div>
                </div>
                <p className="text-sm font-medium text-gray-500 mb-1">Damaged Stock</p>
                <div className="text-3xl font-bold text-gray-900">-</div>
                <p className="text-xs text-gray-500 mt-1">Items rusak</p>
              </div>
            </div>

            {/* Unlocated Items Table */}
            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm">
              <div className="p-6 border-b border-gray-100">
                <h2 className="text-xl font-bold text-gray-900">Unlocated Items</h2>
                <p className="text-sm text-gray-500 mt-1">Items yang perlu dipindahkan ke Warehouse A</p>
              </div>
              <div className="p-6">
                {isLoading ? (
                  <div className="flex items-center justify-center py-12">
                    <Loader2 className="w-8 h-8 animate-spin text-gray-400" />
                  </div>
                ) : unlocatedStock?.data?.length === 0 ? (
                  <div className="text-center py-12">
                    <Package className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p className="text-gray-500">Tidak ada item unlocated</p>
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="table-modern">
                      <thead>
                        <tr>
                          <th>Product</th>
                          <th>SKU</th>
                          <th>Quantity</th>
                          <th>Location</th>
                          <th className="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        {unlocatedStock?.data?.map((item: any) => (
                          <tr key={item.id}>
                            <td className="font-medium">{item.product?.name || '-'}</td>
                            <td>{item.product?.sku || '-'}</td>
                            <td>
                              <span className="badge-modern badge-info">{item.qty} pcs</span>
                            </td>
                            <td>
                              <span className="badge-modern badge-warning">{item.location}</span>
                            </td>
                            <td className="text-right">
                              <button
                                onClick={() => router.push('/warehouse/move')}
                                className="btn-modern bg-gradient-to-r from-green-600 to-emerald-600 text-white text-xs py-1.5 px-3"
                              >
                                Move
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}

export default function WarehousePage() {
  return (
    <QueryClientProvider client={queryClient}>
      <WarehouseContent />
    </QueryClientProvider>
  );
}