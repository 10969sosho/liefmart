'use client';

import * as React from 'react';
import { useRouter, useParams } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { Upload, Plus, Lock, Unlock, FileText, Eye, Trash2, Edit, Search } from 'lucide-react';
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

const platformNames: Record<string, string> = {
  shopee: 'Shopee Lamourad',
  shopee2: 'Shopee Liefmarket',
  tiktok: 'Tiktok Lamourad',
  tiktok2: 'Tiktok Liefmarket',
};

function FinancePlatformContent() {
  const router = useRouter();
  const params = useParams();
  const platform = params.platform as string;
  
  const [search, setSearch] = React.useState('');
  const [page, setPage] = React.useState(1);
  const [status, setStatus] = React.useState('');

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['finance', platform, page, search, status],
    queryFn: async () => {
      const response = await financeApi.getByPlatform(platform as any, {
        page,
        per_page: 20,
        status: status || undefined,
      });
      return response;
    },
  });

  const handleLock = async (id: number) => {
    if (confirm('Lock transaksi ini? Transaksi yang terkunci tidak bisa diedit/dihapus.')) {
      try {
        await financeApi.lock(platform, id);
        refetch();
        alert('Transaksi berhasil dikunci');
      } catch (error) {
        alert('Gagal lock transaksi');
      }
    }
  };

  const handleUnlock = async (id: number) => {
    if (confirm('Unlock transaksi ini?')) {
      try {
        await financeApi.unlock(platform, id);
        refetch();
        alert('Transaksi berhasil di-unlock');
      } catch (error) {
        alert('Gagal unlock transaksi');
      }
    }
  };

  const handleDelete = async (id: number) => {
    if (confirm('Hapus transaksi ini?')) {
      try {
        await financeApi.delete(platform, id);
        refetch();
        alert('Transaksi berhasil dihapus');
      } catch (error) {
        alert('Gagal menghapus transaksi');
      }
    }
  };

  const handlePrintInvoice = async (id: number) => {
    try {
      const blob = await financeApi.printInvoice(platform, id);
      const url = window.URL.createObjectURL(blob);
      window.open(url, '_blank');
    } catch (error) {
      alert('Gagal generate invoice');
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
                  <h1 className="text-3xl font-bold tracking-tight">
                    Finance - {platformNames[platform] || platform}
                  </h1>
                  <p className="text-muted-foreground">
                    Kelola pembayaran dan invoice {platformNames[platform]}
                  </p>
                </div>
                <div className="flex gap-2">
                  <Button
                    variant="default"
                    onClick={() => router.push(`/finance/${platform}/import`)}
                  >
                    <Upload className="w-4 h-4 mr-2" />
                    Import Excel
                  </Button>
                  <Button
                    variant="outline"
                    onClick={() => router.push(`/finance/${platform}/manual`)}
                  >
                    <Plus className="w-4 h-4 mr-2" />
                    Input Manual
                  </Button>
                  <Button
                    variant="outline"
                    onClick={() => router.push('/finance')}
                  >
                    Kembali
                  </Button>
                </div>
              </div>

              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between flex-wrap gap-4">
                    <CardTitle>Daftar Transaksi</CardTitle>
                    <div className="flex items-center gap-2 flex-wrap">
                      <div className="relative">
                        <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                          placeholder="Cari transaksi..."
                          value={search}
                          onChange={(e) => setSearch(e.target.value)}
                          className="pl-8 w-64"
                        />
                      </div>
                      
                      <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                      >
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="locked">Locked</option>
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
                            <TableHead>No. Transaksi</TableHead>
                            <TableHead>No. Order</TableHead>
                            <TableHead>Tanggal Bayar</TableHead>
                            <TableHead>Jumlah</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Invoice</TableHead>
                            <TableHead className="text-right">Aksi</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {data?.data?.map((transaction: any) => (
                            <TableRow key={transaction.id}>
                              <TableCell className="font-medium">
                                {transaction.transaction_number}
                              </TableCell>
                              <TableCell>{transaction.order?.order_number || '-'}</TableCell>
                              <TableCell>{formatDate(transaction.payment_date)}</TableCell>
                              <TableCell>{formatCurrency(transaction.amount)}</TableCell>
                              <TableCell>
                                <span
                                  className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    transaction.status === 'paid'
                                      ? 'bg-green-100 text-green-800'
                                      : transaction.status === 'locked'
                                      ? 'bg-gray-100 text-gray-800'
                                      : 'bg-yellow-100 text-yellow-800'
                                  }`}
                                >
                                  {transaction.status}
                                </span>
                              </TableCell>
                              <TableCell>
                                {transaction.invoice_number ? (
                                  <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => handlePrintInvoice(transaction.id)}
                                  >
                                    <FileText className="w-4 h-4 mr-1" />
                                    {transaction.invoice_number}
                                  </Button>
                                ) : (
                                  <span className="text-muted-foreground text-sm">-</span>
                                )}
                              </TableCell>
                              <TableCell className="text-right">
                                <div className="flex items-center justify-end gap-2">
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => router.push(`/finance/${platform}/${transaction.id}`)}
                                  >
                                    <Eye className="w-4 h-4" />
                                  </Button>
                                  
                                  {transaction.is_locked ? (
                                    <Button
                                      variant="ghost"
                                      size="icon"
                                      onClick={() => handleUnlock(transaction.id)}
                                    >
                                      <Unlock className="w-4 h-4 text-orange-600" />
                                    </Button>
                                  ) : (
                                    <>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleLock(transaction.id)}
                                      >
                                        <Lock className="w-4 h-4 text-blue-600" />
                                      </Button>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleDelete(transaction.id)}
                                      >
                                        <Trash2 className="w-4 h-4 text-red-600" />
                                      </Button>
                                    </>
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

export default function FinancePlatformPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <FinancePlatformContent />
    </QueryClientProvider>
  );
}
