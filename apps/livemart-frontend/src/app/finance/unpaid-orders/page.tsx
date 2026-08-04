'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { AlertCircle, Eye, DollarSign } from 'lucide-react';
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
import { financeApi } from '@/lib/api';
import { formatCurrency, formatDate } from '@/lib/utils';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

function UnpaidOrdersContent() {
  const router = useRouter();
  const [platform, setPlatform] = React.useState('');
  const [search, setSearch] = React.useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['unpaid-orders', platform],
    queryFn: async () => {
      const response = await financeApi.getUnpaidOrders(platform || undefined);
      return response.data;
    },
  });

  const filteredData = React.useMemo(() => {
    if (!data) return [];
    if (!search) return data;
    
    return data.filter((order: any) => 
      order.order_number?.toLowerCase().includes(search.toLowerCase()) ||
      order.customer?.name?.toLowerCase().includes(search.toLowerCase())
    );
  }, [data, search]);

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
                  <h1 className="text-3xl font-bold tracking-tight">Unpaid Orders</h1>
                  <p className="text-muted-foreground">
                    Order yang belum dibayar dari semua platform
                  </p>
                </div>
                <Button
                  variant="outline"
                  onClick={() => router.push('/finance')}
                >
                  Kembali
                </Button>
              </div>

              {/* Summary Cards */}
              <div className="grid gap-4 md:grid-cols-4">
                <Card>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Total Unpaid</CardTitle>
                    <AlertCircle className="h-4 w-4 text-muted-foreground" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">{filteredData.length}</div>
                    <p className="text-xs text-muted-foreground">orders</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Total Amount</CardTitle>
                    <DollarSign className="h-4 w-4 text-muted-foreground" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">
                      {formatCurrency(
                        filteredData.reduce((sum: number, order: any) => sum + (order.total_amount || 0), 0)
                      )}
                    </div>
                    <p className="text-xs text-muted-foreground">total belum dibayar</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Shopee</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">
                      {filteredData.filter((o: any) => o.platform?.includes('shopee')).length}
                    </div>
                    <p className="text-xs text-muted-foreground">unpaid orders</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Tiktok</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">
                      {filteredData.filter((o: any) => o.platform?.includes('tiktok')).length}
                    </div>
                    <p className="text-xs text-muted-foreground">unpaid orders</p>
                  </CardContent>
                </Card>
              </div>

              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between flex-wrap gap-4">
                    <CardTitle>Daftar Order Belum Dibayar</CardTitle>
                    <div className="flex items-center gap-2">
                      <Input
                        placeholder="Cari order..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-64"
                      />
                      
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
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>No. Order</TableHead>
                          <TableHead>Tanggal Order</TableHead>
                          <TableHead>Platform</TableHead>
                          <TableHead>Customer</TableHead>
                          <TableHead>Total</TableHead>
                          <TableHead>Umur (hari)</TableHead>
                          <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {filteredData.length === 0 ? (
                          <TableRow>
                            <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                              Tidak ada order yang belum dibayar
                            </TableCell>
                          </TableRow>
                        ) : (
                          filteredData.map((order: any) => {
                            const orderDate = new Date(order.order_date);
                            const today = new Date();
                            const daysDiff = Math.floor((today.getTime() - orderDate.getTime()) / (1000 * 60 * 60 * 24));
                            
                            return (
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
                                  <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    daysDiff > 30 ? 'bg-red-100 text-red-800' :
                                    daysDiff > 14 ? 'bg-yellow-100 text-yellow-800' :
                                    'bg-green-100 text-green-800'
                                  }`}>
                                    {daysDiff} hari
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
                                    <Button
                                      variant="outline"
                                      size="sm"
                                      onClick={() => router.push(`/finance/${order.platform}/manual`)}
                                    >
                                      Bayar
                                    </Button>
                                  </div>
                                </TableCell>
                              </TableRow>
                            );
                          })
                        )}
                      </TableBody>
                    </Table>
                  )}
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Informasi</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2 text-sm text-muted-foreground">
                    <p>
                      <strong>Warna Indikator Umur:</strong>
                    </p>
                    <ul className="list-disc list-inside ml-2 space-y-1">
                      <li><span className="text-green-600">Hijau:</span> 0-14 hari</li>
                      <li><span className="text-yellow-600">Kuning:</span> 15-30 hari</li>
                      <li><span className="text-red-600">Merah:</span> Lebih dari 30 hari</li>
                    </ul>
                    <p className="mt-3">
                      Klik tombol "Bayar" untuk input pembayaran secara manual.
                    </p>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}

export default function UnpaidOrdersPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <UnpaidOrdersContent />
    </QueryClientProvider>
  );
}
