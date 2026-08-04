'use client';

import * as React from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Save } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { financeApi } from '@/lib/api';

const platformNames: Record<string, string> = {
  shopee: 'Shopee Lamourad',
  shopee2: 'Shopee Liefmarket',
  tiktok: 'Tiktok Lamourad',
  tiktok2: 'Tiktok Liefmarket',
};

const manualPaymentSchema = z.object({
  order_id: z.number().min(1, 'Order ID wajib diisi'),
  amount: z.number().min(1, 'Jumlah pembayaran wajib diisi'),
  payment_date: z.string().min(1, 'Tanggal bayar wajib diisi'),
  notes: z.string().optional(),
});

type ManualPaymentFormData = z.infer<typeof manualPaymentSchema>;

export default function FinanceManualPage() {
  const router = useRouter();
  const params = useParams();
  const platform = params.platform as string;
  
  const [isLoading, setIsLoading] = React.useState(false);
  const [error, setError] = React.useState('');

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ManualPaymentFormData>({
    resolver: zodResolver(manualPaymentSchema),
    defaultValues: {
      payment_date: new Date().toISOString().split('T')[0],
    },
  });

  const onSubmit = async (data: ManualPaymentFormData) => {
    try {
      setIsLoading(true);
      setError('');

      const response = await financeApi.manualStore(platform, data);

      if (response.success) {
        alert('Pembayaran berhasil disimpan');
        router.push(`/finance/${platform}`);
      } else {
        setError(response.message || 'Gagal menyimpan pembayaran');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Terjadi kesalahan');
    } finally {
      setIsLoading(false);
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
                    Input Manual Pembayaran - {platformNames[platform] || platform}
                  </h1>
                  <p className="text-muted-foreground">
                    Input pembayaran secara manual
                  </p>
                </div>
                <Button
                  variant="outline"
                  onClick={() => router.push(`/finance/${platform}`)}
                >
                  Kembali
                </Button>
              </div>

              <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                {error && (
                  <div className="bg-red-50 text-red-600 p-3 rounded-md text-sm">
                    {error}
                  </div>
                )}

                <Card>
                  <CardHeader>
                    <CardTitle>Informasi Pembayaran</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                      <div className="space-y-2">
                        <Label htmlFor="order_id">Order ID *</Label>
                        <Input
                          id="order_id"
                          type="number"
                          placeholder="Masukkan ID order"
                          {...register('order_id', { valueAsNumber: true })}
                          disabled={isLoading}
                        />
                        {errors.order_id && (
                          <p className="text-sm text-red-600">{errors.order_id.message}</p>
                        )}
                        <p className="text-xs text-muted-foreground">
                          ID order yang akan dibayar
                        </p>
                      </div>

                      <div className="space-y-2">
                        <Label htmlFor="payment_date">Tanggal Bayar *</Label>
                        <Input
                          id="payment_date"
                          type="date"
                          {...register('payment_date')}
                          disabled={isLoading}
                        />
                        {errors.payment_date && (
                          <p className="text-sm text-red-600">{errors.payment_date.message}</p>
                        )}
                      </div>

                      <div className="space-y-2">
                        <Label htmlFor="amount">Jumlah Pembayaran *</Label>
                        <Input
                          id="amount"
                          type="number"
                          step="0.01"
                          placeholder="0"
                          {...register('amount', { valueAsNumber: true })}
                          disabled={isLoading}
                        />
                        {errors.amount && (
                          <p className="text-sm text-red-600">{errors.amount.message}</p>
                        )}
                        <p className="text-xs text-muted-foreground">
                          Total jumlah yang dibayarkan
                        </p>
                      </div>

                      <div className="space-y-2">
                        <Label htmlFor="notes">Catatan (Opsional)</Label>
                        <Input
                          id="notes"
                          type="text"
                          placeholder="Catatan tambahan"
                          {...register('notes')}
                          disabled={isLoading}
                        />
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <div className="flex justify-end gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => router.push(`/finance/${platform}`)}
                    disabled={isLoading}
                  >
                    Batal
                  </Button>
                  <Button type="submit" disabled={isLoading}>
                    <Save className="w-4 h-4 mr-2" />
                    {isLoading ? 'Menyimpan...' : 'Simpan Pembayaran'}
                  </Button>
                </div>
              </form>

              <Card>
                <CardHeader>
                  <CardTitle>Informasi</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2 text-sm text-muted-foreground">
                    <p>
                      <strong>Input Manual Pembayaran:</strong>
                    </p>
                    <ul className="list-disc list-inside ml-2 space-y-1">
                      <li>Pastikan Order ID sudah ada di sistem</li>
                      <li>Jumlah pembayaran sesuai dengan total order</li>
                      <li>Tanggal bayar sesuai dengan tanggal transfer</li>
                      <li>Sistem akan otomatis generate invoice</li>
                      <li>Status order akan berubah menjadi "Paid"</li>
                    </ul>
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
