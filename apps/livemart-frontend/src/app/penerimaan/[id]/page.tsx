'use client';

import * as React from 'react';
import { useRouter, useParams } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { ArrowLeft, Edit, Trash2, Printer, Package, Calendar, User, FileText, CreditCard } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
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

function PenerimaanDetailContent() {
  const router = useRouter();
  const params = useParams();
  const id = params.id as string;

  const { data, isLoading, error } = useQuery({
    queryKey: ['penerimaan', id],
    queryFn: async () => {
      const response = await penerimaanApi.getById(parseInt(id));
      return response;
    },
  });

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
        month: 'long',
        year: 'numeric',
      });
    } catch (error) {
      return '-';
    }
  };

  const handleDelete = async () => {
    if (data?.data?.status !== 'Unlocated') {
      alert('Hanya penerimaan dengan status Unlocated yang dapat dihapus');
      return;
    }

    if (confirm('Apakah Anda yakin ingin menghapus penerimaan ini?')) {
      try {
        await penerimaanApi.delete(parseInt(id));
        router.push('/penerimaan');
      } catch (error: any) {
        alert(error.response?.data?.message || 'Gagal menghapus penerimaan');
      }
    }
  };

  const handlePrint = () => {
    window.print();
  };

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100/50">
        <Navbar />
        <div className="flex">
          <Sidebar />
          <main className="flex-1 p-8">
            <div className="max-w-5xl mx-auto space-y-6">
              <div className="h-8 bg-gray-200 rounded w-1/4 skeleton-shimmer"></div>
              <div className="h-96 bg-gray-200 rounded-xl skeleton-shimmer"></div>
            </div>
          </main>
        </div>
      </div>
    );
  }

  if (error || !data?.success) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100/50">
        <Navbar />
        <div className="flex">
          <Sidebar />
          <main className="flex-1 p-8">
            <div className="max-w-5xl mx-auto">
              <Card>
                <CardContent className="p-12 text-center">
                  <Package className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                  <h3 className="text-xl font-bold text-gray-900 mb-2">Data Tidak Ditemukan</h3>
                  <p className="text-gray-500 mb-6">Penerimaan yang Anda cari tidak ditemukan</p>
                  <Button onClick={() => router.push('/penerimaan')}>
                    Kembali ke Daftar
                  </Button>
                </CardContent>
              </Card>
            </div>
          </main>
        </div>
      </div>
    );
  }

  const penerimaan = data.data;

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100/50">
      <Navbar />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-8">
          <div className="max-w-5xl mx-auto space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <button
                  onClick={() => router.push('/penerimaan')}
                  className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  <ArrowLeft className="w-5 h-5 text-gray-600" />
                </button>
                <div>
                  <h1 className="text-3xl font-bold text-gray-900">Detail Penerimaan</h1>
                  <p className="text-gray-500 mt-1">#{penerimaan.kode_penerimaan}</p>
                </div>
              </div>
              <div className="flex gap-2">
                <Button variant="outline" onClick={handlePrint}>
                  <Printer className="w-4 h-4 mr-2" />
                  Print
                </Button>
                {penerimaan.status === 'Unlocated' && (
                  <>
                    <Button
                      variant="outline"
                      onClick={() => router.push(`/penerimaan/${id}/edit`)}
                    >
                      <Edit className="w-4 h-4 mr-2" />
                      Edit
                    </Button>
                    <Button variant="destructive" onClick={handleDelete}>
                      <Trash2 className="w-4 h-4 mr-2" />
                      Hapus
                    </Button>
                  </>
                )}
              </div>
            </div>

            {/* Info Cards */}
            <div className="grid grid-cols-3 gap-6">
              <Card>
                <CardContent className="p-6">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                      <FileText className="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Status</p>
                      <Badge variant={penerimaan.status === 'Located' ? 'success' : 'warning'}>
                        {penerimaan.status}
                      </Badge>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardContent className="p-6">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                      <CreditCard className="w-6 h-6 text-green-600" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Metode Pembayaran</p>
                      <p className="font-bold text-gray-900">{penerimaan.metode_pembayaran}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardContent className="p-6">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
                      <Package className="w-6 h-6 text-purple-600" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Total Item</p>
                      <p className="font-bold text-gray-900">{penerimaan.details?.length || 0} Item</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Informasi Umum */}
            <Card>
              <CardHeader>
                <CardTitle>Informasi Umum</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 gap-6">
                  <div>
                    <label className="text-sm font-medium text-gray-500">Kode Penerimaan</label>
                    <p className="text-lg font-bold text-gray-900 mt-1">{penerimaan.kode_penerimaan}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-gray-500">Nomor PO</label>
                    <p className="text-lg font-bold text-gray-900 mt-1">{penerimaan.nomor_po}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-gray-500">Tanggal Penerimaan</label>
                    <p className="text-gray-900 mt-1 flex items-center gap-2">
                      <Calendar className="w-4 h-4 text-gray-400" />
                      {formatDate(penerimaan.tanggal_penerimaan)}
                    </p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-gray-500">Kategori Utama</label>
                    <p className="text-gray-900 mt-1">{penerimaan.main_category?.name || '-'}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-gray-500">Kategori Pajak</label>
                    <p className="text-gray-900 mt-1">{penerimaan.tax_category?.name || '-'}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-gray-500">Metode Pembayaran</label>
                    <p className="text-gray-900 mt-1">{penerimaan.metode_pembayaran}</p>
                  </div>
                  {penerimaan.tanggal_jatuh_tempo && (
                    <div>
                      <label className="text-sm font-medium text-gray-500">Tanggal Jatuh Tempo</label>
                      <p className="text-gray-900 mt-1">{formatDate(penerimaan.tanggal_jatuh_tempo)}</p>
                    </div>
                  )}
                  <div>
                    <label className="text-sm font-medium text-gray-500">Lokasi</label>
                    <p className="text-gray-900 mt-1">{penerimaan.lokasi?.name || '-'}</p>
                  </div>
                  {penerimaan.catatan && (
                    <div className="col-span-2">
                      <label className="text-sm font-medium text-gray-500">Catatan</label>
                      <p className="text-gray-900 mt-1">{penerimaan.catatan}</p>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Detail Items */}
            <Card>
              <CardHeader>
                <CardTitle>Detail Item Penerimaan</CardTitle>
              </CardHeader>
              <CardContent>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-12">No</TableHead>
                      <TableHead>Nama Produk</TableHead>
                      <TableHead className="text-center">Qty</TableHead>
                      <TableHead>Satuan</TableHead>
                      <TableHead className="text-right">Harga HPP</TableHead>
                      <TableHead className="text-right">Diskon</TableHead>
                      <TableHead className="text-right">Subtotal</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {penerimaan.details?.map((detail: any, index: number) => {
                      const totalDiskonPersen = [
                        detail.diskon_persen_1,
                        detail.diskon_persen_2,
                        detail.diskon_persen_3,
                        detail.diskon_persen_4,
                        detail.diskon_persen_5,
                      ].reduce((sum, val) => sum + (val || 0), 0);

                      const totalDiskonNominal = [
                        detail.diskon_nominal_1,
                        detail.diskon_nominal_2,
                        detail.diskon_nominal_3,
                        detail.diskon_nominal_4,
                        detail.diskon_nominal_5,
                      ].reduce((sum, val) => sum + (val || 0), 0);

                      return (
                        <TableRow key={detail.id}>
                          <TableCell className="text-center font-medium">{index + 1}</TableCell>
                          <TableCell>
                            <div>
                              <p className="font-medium text-gray-900">{detail.product_name}</p>
                              {detail.catatan && (
                                <p className="text-xs text-gray-500 mt-1">{detail.catatan}</p>
                              )}
                              {detail.is_free && (
                                <Badge variant="success" className="mt-1">FREE</Badge>
                              )}
                            </div>
                          </TableCell>
                          <TableCell className="text-center font-medium">{detail.qty}</TableCell>
                          <TableCell>{detail.satuan_name}</TableCell>
                          <TableCell className="text-right font-medium">
                            {formatCurrency(detail.harga_hpp)}
                          </TableCell>
                          <TableCell className="text-right">
                            {totalDiskonPersen > 0 && (
                              <div className="text-sm text-orange-600 font-medium">
                                {totalDiskonPersen}%
                              </div>
                            )}
                            {totalDiskonNominal > 0 && (
                              <div className="text-sm text-orange-600 font-medium">
                                {formatCurrency(totalDiskonNominal)}
                              </div>
                            )}
                            {totalDiskonPersen === 0 && totalDiskonNominal === 0 && '-'}
                          </TableCell>
                          <TableCell className="text-right font-bold text-gray-900">
                            {formatCurrency(detail.subtotal)}
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>

                {/* Total */}
                <div className="mt-6 pt-6 border-t border-gray-200">
                  <div className="flex justify-end">
                    <div className="w-80">
                      <div className="flex justify-between mb-4">
                        <span className="text-gray-600">Total Item:</span>
                        <span className="font-semibold">{penerimaan.details?.length || 0} Item</span>
                      </div>
                      <div className="flex justify-between text-xl font-bold pt-4 border-t border-gray-300">
                        <span>Total Harga:</span>
                        <span className="text-blue-600">{formatCurrency(penerimaan.total_harga)}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </main>
      </div>
    </div>
  );
}

export default function PenerimaanDetailPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <PenerimaanDetailContent />
    </QueryClientProvider>
  );
}
