'use client';

import * as React from 'react';
import { useRouter, useParams } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { ArrowLeft, Eye, Loader2, ShoppingCart, Calendar, User } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { salesApi } from '@/lib/api';
import { formatCurrency, formatDate } from '@/lib/utils';

const queryClient = new QueryClient({
  defaultOptions: { queries: { refetchOnWindowFocus: false, retry: 1 } },
});

function OrderDetailContent() {
  const router = useRouter();
  const params = useParams();
  const orderId = Number(params.id);

  const { data: order, isLoading } = useQuery({
    queryKey: ['order', orderId],
    queryFn: async () => {
      const response = await salesApi.getOrderById(orderId);
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

  if (!order) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Navbar />
        <div className="flex">
          <Sidebar />
          <main className="flex-1 p-8">
            <div className="text-center">
              <h2 className="text-xl font-semibold text-gray-900">Order tidak ditemukan</h2>
              <button
                onClick={() => router.push('/sales/list')}
                className="mt-4 text-blue-600 hover:text-blue-700"
              >
                Kembali ke List Orders
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
          <div className="max-w-5xl mx-auto space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <button
                  onClick={() => router.push('/sales/list')}
                  className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 mb-2"
                >
                  <ArrowLeft className="w-4 h-4" />
                  Kembali
                </button>
                <h1 className="text-3xl font-bold text-gray-900">Order Detail</h1>
                <p className="text-gray-500 mt-1">{order.order_number}</p>
              </div>
              <div className="flex gap-2">
                <span className={`badge-modern ${
                  order.status === 'completed' ? 'badge-success' :
                  order.status === 'pending' ? 'badge-warning' :
                  'badge-danger'
                }`}>
                  {order.status}
                </span>
                <span className={`badge-modern ${
                  order.payment_status === 'paid' ? 'badge-success' :
                  order.payment_status === 'refunded' ? 'badge-info' :
                  'badge-warning'
                }`}>
                  {order.payment_status}
                </span>
              </div>
            </div>

            {/* Order Info */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div className="flex items-center gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <ShoppingCart className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Platform</p>
                    <p className="text-sm font-semibold text-gray-900 capitalize">{order.platform}</p>
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div className="flex items-center gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <Calendar className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Order Date</p>
                    <p className="text-sm font-semibold text-gray-900">{formatDate(order.order_date)}</p>
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div className="flex items-center gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <User className="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Customer</p>
                    <p className="text-sm font-semibold text-gray-900">{order.customer?.name || 'Guest'}</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Items Table */}
            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm">
              <div className="p-6 border-b border-gray-100">
                <h2 className="text-xl font-bold text-gray-900">Order Items</h2>
              </div>
              <div className="p-6">
                <div className="overflow-x-auto">
                  <table className="table-modern">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th className="text-right">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      {order.items?.map((item: any) => (
                        <tr key={item.id}>
                          <td className="font-medium">
                            {item.product?.name || item.platform_product_name || '-'}
                          </td>
                          <td>
                            <span className="badge-modern badge-info">{item.qty}</span>
                          </td>
                          <td>{formatCurrency(item.price)}</td>
                          <td className="text-right font-semibold">{formatCurrency(item.subtotal)}</td>
                        </tr>
                      ))}
                    </tbody>
                    <tfoot>
                      <tr className="border-t-2 border-gray-200">
                        <td colSpan={3} className="text-right font-bold text-gray-900">Total:</td>
                        <td className="text-right text-xl font-bold text-gray-900">
                          {formatCurrency(order.total_amount)}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            {/* Actions */}
            <div className="flex justify-end gap-3">
              <button
                onClick={() => router.push('/sales/list')}
                className="btn-modern border border-gray-300 text-gray-700 hover:bg-gray-50"
              >
                Kembali
              </button>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}

export default function OrderDetailPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <OrderDetailContent />
    </QueryClientProvider>
  );
}