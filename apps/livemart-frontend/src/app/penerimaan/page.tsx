'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { Plus, Search, FileDown, Eye, Edit, Trash2 } from 'lucide-react';
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
import { penerimaanApi } from '@/lib/api';
import { formatCurrency, formatDate } from '@/lib/utils';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

function PenerimaanContent() {
  const router = useRouter();
  const [search, setSearch] = React.useState('');
  const [page, setPage] = React.useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['penerimaan', page, search],
    queryFn: async () => {
      const response = await penerimaanApi.getAll({
        page,
        per_page: 20,
        search,
      });
      return response;
    },
  });

  const handleDelete = async (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus penerimaan ini?')) {
      try {
        await penerimaanApi.delete(id);
        window.location.reload();
      } catch (error) {
        alert('Gagal menghapus penerimaan');
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
                  <h1 className="text-3xl font-bold tracking-tight">Penerimaan Barang</h1>
                  <p className="text-muted-foreground">
                    Kelola penerimaan barang dari supplier
                  </p>
                </div>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    onClick={() => router.push('/penerimaan/create')}
                  >
                    <Plus className="w-4 h-4 mr-2" />
                    Tambah Penerimaan
                  </Button>
                  <Button variant="outline">
                    <FileDown className="w-4 h-4 mr-2" />
                    Export
                  </Button>
                </div>
              </div>

              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle>Daftar Penerimaan</CardTitle>
                    <div className="flex items-center gap-2">
                      <div className="relative">
                        <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                          placeholder="Cari penerimaan..."
                          value={search}
                          onChange={(e) => setSearch(e.target.value)}
                          className="pl-8 w-64"
                        />
                      </div>
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
                            <TableHead>No. Penerimaan</TableHead>
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Supplier</TableHead>
                            <TableHead>Total Item</TableHead>
                            <TableHead>Total Harga</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Aksi</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {data?.data?.map((item: any) => (
                            <TableRow key={item.id}>
                              <TableCell className="font-medium">
                                {item.penerimaan_number}
                              </TableCell>
                              <TableCell>{formatDate(item.date)}</TableCell>
                              <TableCell>{item.supplier_name || '-'}</TableCell>
                              <TableCell>{item.total_items}</TableCell>
                              <TableCell>{formatCurrency(item.total_amount)}</TableCell>
                              <TableCell>
                                <span
                                  className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    item.status === 'finalized'
                                      ? 'bg-green-100 text-green-800'
                                      : 'bg-yellow-100 text-yellow-800'
                                  }`}
                                >
                                  {item.status}
                                </span>
                              </TableCell>
                              <TableCell className="text-right">
                                <div className="flex items-center justify-end gap-2">
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => router.push(`/penerimaan/${item.id}`)}
                                  >
                                    <Eye className="w-4 h-4" />
                                  </Button>
                                  {item.status === 'draft' && (
                                    <>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => router.push(`/penerimaan/${item.id}/edit`)}
                                      >
                                        <Edit className="w-4 h-4" />
                                      </Button>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleDelete(item.id)}
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

export default function PenerimaanPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <PenerimaanContent />
    </QueryClientProvider>
  );
}
