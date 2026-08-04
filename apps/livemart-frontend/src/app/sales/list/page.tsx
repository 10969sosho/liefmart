'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { Eye, Trash2, Search, Calendar, Filter } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { salesApi } from '@/lib/api';
import { formatCurrency, formatDate } from '@/lib/utils';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

function SalesListContent() {
  const router = useRouter();
  const [search, setSearch] = React.useState('');
  const [page, setPage] = React.useState(1);
  const [platform, setPlatform] = React.useState('');
  const [status, setStatus] = React.useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['sales-orders', page, search, platform, status],
    queryFn: async () => {
      const response = await salesApi.getOrders({
        page,
        per_page: 20,
        search,
        platform: platform || undefined,
        status: status || undefined,
      });
      return response;
    },
  });

  const handleDelete = async (id: number, orderPlatform: string) => {
    if (confirm('Apakah Anda yakin ingin menghapus order ini?')) {
      try {
        await salesApi.deleteOrder(id, orderPlatform);
        window.location.reload();
      } catch (error) {
        alert('Gagal menghapus order');
      }
    }
  };

  return (
    <div className="h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 flex overflow-hidden">
        <Sidebar />
        <main className="flex-1 overflow-y-auto bg-gray-50">
          <div className="container mx-auto p-6">
            <div className="space-y-6">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-3xl font-bold tracking-tight">List Semua Penjualan</h1>
                  <p className="text-muted-foreground">
                    Kelola semua transaksi penjualan dari semua platform
                  </p>
                </div>
                <Button
                  variant="outline"
                  onClick={() => router.push('/sales/choose-type')}
                >
                  Kembali
                </Button>
              </div>

              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between flex-wrap gap-4">
                    <CardTitle>Daftar Order</CardTitle>
                    <div className="flex items-center gap-2 flex-wrap">
                      <div className="relative">
                        <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                          placeholder="Cari order..."
                          value={search}
                          onChange={(e) => setSearch(e.target.value)}
                          className="pl-8 w-64"
                        />
                      </div>
                      
                      <select
                        value={platform}
                        onChange={(e) => setPlatform(e.target.value)}
                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                      >
                        <option value="">Semua Platform</option>
                        <option value="shopee">Shopee Lamourad</option>
                        <option value="shopee2">Shopee Liefmarket</option>
                        <option value="tiktok">Tiktok Lamourad</option>
                        <option value="tiktok2">Tiktok Liefmarket</option>
                        <option value="offline">Offline</option>
                      </select>

                      <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                      >
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                      </select>
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  {isLoading ? (
                    <div className="space-y-3">
                      {[1, 2, 3, 4, 5].map((i) => (
                        <div key={i} className="h-12 bg-gray-200 animate-pulse rounded"></div>
                      ))}
                    </div>
                  ) : (
                    <>
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead>No. Order</TableHead>
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Platform</TableHead>
                            <TableHead>Customer</TableHead>
                            <TableHead>Total Harga</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Status Bayar</TableHead>
                            <TableHead className="text-right">Aksi</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {data?.data?.map((order: any) => (
                            <TableRow key={order.id}>
                              <TableCell className="font-medium">
                                {order.order_number}
                              </TableCell>
                              <TableCell>{formatDate(order.order_date)}</TableCell>
                              <TableCell>
                                <span className="capitalize inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                  {order.platform}
                                </span>
                              </TableCell>
                              <TableCell>{order.customer?.name || '-'}</TableCell>
                              <TableCell>{formatCurrency(order.total_amount)}</TableCell>
                              <TableCell>
                                <span
                                  className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    order.status === 'completed'
                                      ? 'bg-green-100 text-green-800'
                                      : order.status === 'processing'
                                      ? 'bg-yellow-100 text-yellow-800'
                                      : order.status === 'cancelled'
                                      ? 'bg-red-100 text-red-800'
                                      : 'bg-gray-100 text-gray-800'
                                  }`}
                                >
                                  {order.status}
                                </span>
                              </TableCell>
                              <TableCell>
                                <span
                                  className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    order.payment_status === 'paid'
                                      ? 'bg-green-100 text-green-800'
                                      : order.payment_status === 'refunded'
                                      ? 'bg-orange-100 text-orange-800'
                                      : 'bg-gray-100 text-gray-800'
                                  }`}
                                >
                                  {order.payment_status}
                                </span>
                              </TableCell>
                              <TableCell className="text-right">
                                <div className="flex items-center justify-end gap-2">
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => router.push(`/sales/orders/${order.id}`)}
                                  >
                                    <Eye className="w-4 h-4" />
                                  </Button>
                                  {order.status === 'pending' && (
                                    <Button
                                      variant="ghost"
                                      size="icon"
                                      onClick={() => handleDelete(order.id, order.platform)}
                                    >
                                      <Trash2 className="w-4 h-4 text-red-600" />
                                    </Button>
                                  )}
                                </div>
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>

                      {data && data.total > 0 && (
                        <div className="flex items-center justify-between mt-4">
                          <p className="text-sm text-muted-foreground">
                            Showing {((data.current_page - 1) * data.per_page) + 1} to{' '}
                            {Math.min(data.current_page * data.per_page, data.total)} of{' '}
                            {data.total} results
                          </p>
                          <div className="flex gap-2">
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => setPage(page - 1)}
                              disabled={page === 1}
                            >
                              Previous
                            </Button>
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => setPage(page + 1)}
                              disabled={page === data.last_page}
                            >
                              Next
                            </Button>
                          </div>
                        </div>
                      )}
                    </>
                  )}
                </CardContent>
              </Card>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}

export default function SalesListPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <SalesListContent />
    </QueryClientProvider>
  );
}
