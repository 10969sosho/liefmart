'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { Plus, Search, FileDown, Eye, Edit, Trash2, Filter, X, Calendar } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { penerimaanApi } from '@/lib/api';

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
  const [showFilters, setShowFilters] = React.useState(false);
  const [filters, setFilters] = React.useState({
    status: '',
    metode_pembayaran: '',
    start_date: '',
    end_date: '',
  });

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['penerimaan', page, search, filters],
    queryFn: async () => {
      const response = await penerimaanApi.getAll({
        page,
        per_page: 20,
        search,
        ...filters,
      });
      return response;
    },
  });

  const handleDelete = async (id: number, status: string) => {
    if (status !== 'Unlocated') {
      alert('Hanya penerimaan dengan status Unlocated yang dapat dihapus');
      return;
    }

    if (confirm('Apakah Anda yakin ingin menghapus penerimaan ini?')) {
      try {
        await penerimaanApi.delete(id);
        refetch();
      } catch (error: any) {
        alert(error.response?.data?.message || 'Gagal menghapus penerimaan');
      }
    }
  };

  const handleFilterChange = (key: string, value: string) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    setPage(1);
  };

  const clearFilters = () => {
    setFilters({
      status: '',
      metode_pembayaran: '',
      start_date: '',
      end_date: '',
    });
    setPage(1);
  };

  const formatCurrency = (value: number) => {
    if (!value || isNaN(value)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(value);
  };

  const formatDate = (date: string | null) => {
    if (!date) return '-';
    try {
      const parsed = new Date(date);
      if (isNaN(parsed.getTime())) return '-';
      return parsed.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
    } catch (error) {
      return '-';
    }
  };

  const activeFiltersCount = Object.values(filters).filter(v => v !== '').length;

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100/50">
      <Navbar />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-8">
          <div className="max-w-7xl mx-auto space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-4xl font-bold text-gray-900">Penerimaan Barang</h1>
                <p className="text-gray-500 mt-2">
                  Kelola penerimaan barang dari supplier
                </p>
              </div>
              <div className="flex gap-3">
                <Button
                  variant="outline"
                  onClick={() => {/* TODO: Export */}}
                >
                  <FileDown className="w-4 h-4 mr-2" />
                  Export
                </Button>
                <Button
                  onClick={() => router.push('/penerimaan/create')}
                  className="flex items-center gap-2"
                >
                  <Plus className="w-4 h-4" />
                  Tambah Penerimaan
                </Button>
              </div>
            </div>

            {/* Filters & Table */}
            <Card>
              <CardHeader>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <CardTitle>Daftar Penerimaan</CardTitle>
                    <div className="flex items-center gap-3">
                      <div className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <Input
                          placeholder="Cari kode atau nomor PO..."
                          value={search}
                          onChange={(e) => {
                            setSearch(e.target.value);
                            setPage(1);
                          }}
                          className="pl-10 w-80"
                        />
                      </div>
                      <Button
                        variant="outline"
                        onClick={() => setShowFilters(!showFilters)}
                        className="relative"
                      >
                        <Filter className="w-4 h-4 mr-2" />
                        Filter
                        {activeFiltersCount > 0 && (
                          <span className="absolute -top-1 -right-1 w-5 h-5 bg-blue-600 text-white text-xs rounded-full flex items-center justify-center">
                            {activeFiltersCount}
                          </span>
                        )}
                      </Button>
                    </div>
                  </div>

                  {/* Filter Panel */}
                  {showFilters && (
                    <div className="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-4 animate-fade-in">
                      <div className="grid grid-cols-4 gap-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Status
                          </label>
                          <select
                            value={filters.status}
                            onChange={(e) => handleFilterChange('status', e.target.value)}
                            className="input-modern"
                          >
                            <option value="">Semua Status</option>
                            <option value="Unlocated">Unlocated</option>
                            <option value="Located">Located</option>
                          </select>
                        </div>

                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Metode Pembayaran
                          </label>
                          <select
                            value={filters.metode_pembayaran}
                            onChange={(e) => handleFilterChange('metode_pembayaran', e.target.value)}
                            className="input-modern"
                          >
                            <option value="">Semua Metode</option>
                            <option value="Cash">Cash</option>
                            <option value="Jatuh Tempo">Jatuh Tempo</option>
                          </select>
                        </div>

                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Tanggal Mulai
                          </label>
                          <div className="relative">
                            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                              type="date"
                              value={filters.start_date}
                              onChange={(e) => handleFilterChange('start_date', e.target.value)}
                              className="input-modern pl-10"
                            />
                          </div>
                        </div>

                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Tanggal Akhir
                          </label>
                          <div className="relative">
                            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                              type="date"
                              value={filters.end_date}
                              onChange={(e) => handleFilterChange('end_date', e.target.value)}
                              className="input-modern pl-10"
                            />
                          </div>
                        </div>
                      </div>

                      <div className="flex justify-end gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={clearFilters}
                        >
                          <X className="w-4 h-4 mr-2" />
                          Reset Filter
                        </Button>
                      </div>
                    </div>
                  )}
                </div>
              </CardHeader>
              <CardContent>
                {isLoading ? (
                  <div className="space-y-3">
                    {[1, 2, 3, 4, 5].map((i) => (
                      <div key={i} className="h-16 bg-gray-100 rounded-xl skeleton-shimmer"></div>
                    ))}
                  </div>
                ) : (
                  <>
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Kode Penerimaan</TableHead>
                          <TableHead>No. PO</TableHead>
                          <TableHead>Tanggal</TableHead>
                          <TableHead>Kategori</TableHead>
                          <TableHead>Pembayaran</TableHead>
                          <TableHead className="text-right">Total Harga</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {!data?.data || data.data.length === 0 ? (
                          <TableRow>
                            <TableCell colSpan={8} className="text-center py-12 text-gray-500">
                              {search || activeFiltersCount > 0 
                                ? 'Tidak ada data yang sesuai dengan pencarian atau filter'
                                : 'Belum ada data penerimaan'}
                            </TableCell>
                          </TableRow>
                        ) : (
                          data.data.map((item: any) => (
                            <TableRow key={item.id}>
                              <TableCell className="font-bold text-blue-600">
                                {item.kode_penerimaan}
                              </TableCell>
                              <TableCell className="font-medium">{item.nomor_po}</TableCell>
                              <TableCell>{formatDate(item.tanggal_penerimaan)}</TableCell>
                              <TableCell>
                                <div className="text-sm">
                                  <div className="font-medium text-gray-900">{item.main_category || '-'}</div>
                                  <div className="text-xs text-gray-500">{item.tax_category || '-'}</div>
                                </div>
                              </TableCell>
                              <TableCell>
                                <Badge variant={item.metode_pembayaran === 'Cash' ? 'success' : 'warning'}>
                                  {item.metode_pembayaran}
                                </Badge>
                              </TableCell>
                              <TableCell className="font-bold text-right">{formatCurrency(item.total_harga)}</TableCell>
                              <TableCell>
                                <Badge
                                  variant={
                                    item.status === 'Located'
                                      ? 'success'
                                      : item.status === 'Unlocated'
                                      ? 'warning'
                                      : 'gray'
                                  }
                                >
                                  {item.status}
                                </Badge>
                              </TableCell>
                              <TableCell className="text-right">
                                <div className="flex items-center justify-end gap-2">
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => router.push(`/penerimaan/${item.id}`)}
                                    title="Lihat Detail"
                                  >
                                    <Eye className="w-4 h-4" />
                                  </Button>
                                  {item.status === 'Unlocated' && (
                                    <>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => router.push(`/penerimaan/${item.id}/edit`)}
                                        title="Edit"
                                      >
                                        <Edit className="w-4 h-4" />
                                      </Button>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleDelete(item.id, item.status)}
                                        title="Hapus"
                                      >
                                        <Trash2 className="w-4 h-4 text-red-600" />
                                      </Button>
                                    </>
                                  )}
                                </div>
                              </TableCell>
                            </TableRow>
                          ))
                        )}
                      </TableBody>
                    </Table>

                    {data?.pagination && data.pagination.total > 0 && (
                      <div className="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                        <p className="text-sm text-gray-500">
                          Menampilkan{' '}
                          {((data.pagination.current_page - 1) * data.pagination.per_page) + 1} -{' '}
                          {Math.min(
                            data.pagination.current_page * data.pagination.per_page,
                            data.pagination.total
                          )}{' '}
                          dari {data.pagination.total} data
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
                          <div className="flex items-center gap-1">
                            {Array.from({ length: Math.min(5, data.pagination.last_page) }, (_, i) => {
                              const pageNum = i + 1;
                              return (
                                <Button
                                  key={pageNum}
                                  variant={page === pageNum ? 'default' : 'outline'}
                                  size="sm"
                                  onClick={() => setPage(pageNum)}
                                  className="w-10"
                                >
                                  {pageNum}
                                </Button>
                              );
                            })}
                          </div>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setPage(page + 1)}
                            disabled={page === data.pagination.last_page}
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
