'use client';

import * as React from 'react';
import { useRouter, useParams } from 'next/navigation';
import { Upload, AlertCircle, CheckCircle } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { salesApi } from '@/lib/api';

export default function SalesOnlineImportPage() {
  const router = useRouter();
  const params = useParams();
  const platform = params.platform as string;
  
  const [file, setFile] = React.useState<File | null>(null);
  const [previewData, setPreviewData] = React.useState<any>(null);
  const [isUploading, setIsUploading] = React.useState(false);
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [error, setError] = React.useState('');
  const [success, setSuccess] = React.useState('');

  const platformNames: Record<string, string> = {
    shopee: 'Shopee Lamourad',
    shopee2: 'Shopee Liefmarket',
    tiktok: 'Tiktok Lamourad',
    tiktok2: 'Tiktok Liefmarket',
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setFile(e.target.files[0]);
      setPreviewData(null);
      setError('');
      setSuccess('');
    }
  };

  const handlePreview = async () => {
    if (!file) {
      setError('Pilih file Excel terlebih dahulu');
      return;
    }

    try {
      setIsUploading(true);
      setError('');

      const response = await salesApi.previewImport(platform, file);

      if (response.success) {
        setPreviewData(response.data);
        setSuccess('Preview berhasil. Periksa data sebelum import.');
      } else {
        setError(response.message || 'Gagal preview data');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Terjadi kesalahan saat preview');
    } finally {
      setIsUploading(false);
    }
  };

  const handleProcessImport = async () => {
    if (!previewData) {
      setError('Preview data terlebih dahulu');
      return;
    }

    if (!confirm('Apakah Anda yakin ingin mengimport data ini?')) {
      return;
    }

    try {
      setIsProcessing(true);
      setError('');

      const response = await salesApi.processImport(platform, previewData);

      if (response.success) {
        setSuccess('Import berhasil!');
        setTimeout(() => {
          router.push('/sales/list');
        }, 2000);
      } else {
        setError(response.message || 'Gagal mengimport data');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Terjadi kesalahan saat import');
    } finally {
      setIsProcessing(false);
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
                    Import Excel - {platformNames[platform] || platform}
                  </h1>
                  <p className="text-muted-foreground">
                    Upload file Excel untuk import penjualan
                  </p>
                </div>
                <Button
                  variant="outline"
                  onClick={() => router.push('/sales/online')}
                >
                  Kembali
                </Button>
              </div>

              {error && (
                <div className="bg-red-50 border border-red-200 text-red-600 p-4 rounded-md flex items-start gap-3">
                  <AlertCircle className="w-5 h-5 mt-0.5" />
                  <div className="flex-1">{error}</div>
                </div>
              )}

              {success && (
                <div className="bg-green-50 border border-green-200 text-green-600 p-4 rounded-md flex items-start gap-3">
                  <CheckCircle className="w-5 h-5 mt-0.5" />
                  <div className="flex-1">{success}</div>
                </div>
              )}

              <Card>
                <CardHeader>
                  <CardTitle>Upload File Excel</CardTitle>
                  <CardDescription>
                    Pastikan file Excel sesuai dengan template dari {platformNames[platform]}
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="file">File Excel (.xlsx, .xls)</Label>
                    <Input
                      id="file"
                      type="file"
                      accept=".xlsx,.xls"
                      onChange={handleFileChange}
                      disabled={isUploading || isProcessing}
                    />
                    {file && (
                      <p className="text-sm text-muted-foreground">
                        File terpilih: {file.name} ({(file.size / 1024).toFixed(2)} KB)
                      </p>
                    )}
                  </div>

                  <Button
                    onClick={handlePreview}
                    disabled={!file || isUploading || isProcessing}
                  >
                    <Upload className="w-4 h-4 mr-2" />
                    {isUploading ? 'Uploading...' : 'Preview Data'}
                  </Button>
                </CardContent>
              </Card>

              {previewData && (
                <Card>
                  <CardHeader>
                    <CardTitle>Preview Data Import</CardTitle>
                    <CardDescription>
                      Total: {previewData.total_rows || 0} baris, Valid: {previewData.valid_rows || 0}, Error: {previewData.error_rows || 0}
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="rounded-md border">
                      <div className="max-h-96 overflow-auto">
                        <table className="w-full">
                          <thead className="bg-gray-50 border-b sticky top-0">
                            <tr>
                              <th className="px-4 py-2 text-left text-sm font-medium">#</th>
                              <th className="px-4 py-2 text-left text-sm font-medium">No. Order</th>
                              <th className="px-4 py-2 text-left text-sm font-medium">Tanggal</th>
                              <th className="px-4 py-2 text-left text-sm font-medium">Customer</th>
                              <th className="px-4 py-2 text-left text-sm font-medium">Total</th>
                              <th className="px-4 py-2 text-left text-sm font-medium">Status</th>
                            </tr>
                          </thead>
                          <tbody>
                            {previewData.rows?.slice(0, 50).map((row: any, index: number) => (
                              <tr key={index} className={`border-b ${row.has_error ? 'bg-red-50' : ''}`}>
                                <td className="px-4 py-2 text-sm">{index + 1}</td>
                                <td className="px-4 py-2 text-sm">{row.order_number || '-'}</td>
                                <td className="px-4 py-2 text-sm">{row.order_date || '-'}</td>
                                <td className="px-4 py-2 text-sm">{row.customer_name || '-'}</td>
                                <td className="px-4 py-2 text-sm">{row.total_amount || 0}</td>
                                <td className="px-4 py-2 text-sm">
                                  {row.has_error ? (
                                    <span className="text-red-600 text-xs">{row.error_message}</span>
                                  ) : (
                                    <span className="text-green-600 text-xs">Valid</span>
                                  )}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>

                    {previewData.error_rows > 0 && (
                      <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-md">
                        <p className="text-sm text-yellow-800">
                          <strong>Perhatian:</strong> Ada {previewData.error_rows} baris dengan error. Baris dengan error tidak akan diimport.
                        </p>
                      </div>
                    )}

                    <div className="flex gap-2">
                      <Button
                        onClick={handleProcessImport}
                        disabled={isProcessing || previewData.valid_rows === 0}
                      >
                        {isProcessing ? 'Processing...' : `Import ${previewData.valid_rows} Data`}
                      </Button>
                      <Button
                        variant="outline"
                        onClick={() => {
                          setPreviewData(null);
                          setFile(null);
                          setSuccess('');
                        }}
                        disabled={isProcessing}
                      >
                        Batal
                      </Button>
                    </div>
                  </CardContent>
                </Card>
              )}

              <Card>
                <CardHeader>
                  <CardTitle>Petunjuk Import</CardTitle>
                </CardHeader>
                <CardContent>
                  <ol className="list-decimal list-inside space-y-2 text-sm text-muted-foreground">
                    <li>Download template Excel dari sistem {platformNames[platform]}</li>
                    <li>Pastikan kolom-kolom berikut ada: Order Number, Date, Customer, Amount, Items</li>
                    <li>Jangan ubah format template (nama kolom, urutan, dll)</li>
                    <li>Upload file Excel dan klik "Preview Data"</li>
                    <li>Periksa preview data, pastikan tidak ada error</li>
                    <li>Klik "Import" untuk memproses data</li>
                    <li>Sistem akan otomatis:
                      <ul className="list-disc list-inside ml-6 mt-1 space-y-1">
                        <li>Membuat order baru</li>
                        <li>Mapping produk platform ke produk internal</li>
                        <li>Mengurangi stok barang</li>
                        <li>Generate nomor SJ (Surat Jalan)</li>
                      </ul>
                    </li>
                  </ol>
                </CardContent>
              </Card>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}
