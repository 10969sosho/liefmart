'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Plus, Trash2, Save } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { salesApi } from '@/lib/api';
import { formatCurrency } from '@/lib/utils';

const offlineSaleSchema = z.object({
  customer_id: z.number().optional(),
  order_date: z.string().min(1, 'Tanggal order wajib diisi'),
  sj_number: z.string().optional(),
});

type OfflineSaleFormData = z.infer<typeof offlineSaleSchema>;

interface OrderItem {
  product_id: number;
  product_name: string;
  qty: number;
  price: number;
  subtotal: number;
}

export default function SalesOfflinePage() {
  const router = useRouter();
  const [items, setItems] = React.useState<OrderItem[]>([]);
  const [isLoading, setIsLoading] = React.useState(false);
  const [error, setError] = React.useState('');

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<OfflineSaleFormData>({
    resolver: zodResolver(offlineSaleSchema),
    defaultValues: {
      order_date: new Date().toISOString().split('T')[0],
    },
  });

  const addItem = () => {
    setItems([
      ...items,
      {
        product_id: 0,
        product_name: '',
        qty: 1,
        price: 0,
        subtotal: 0,
      },
    ]);
  };

  const removeItem = (index: number) => {
    setItems(items.filter((_, i) => i !== index));
  };

  const updateItem = (index: number, field: keyof OrderItem, value: any) => {
    const updatedItems = [...items];
    updatedItems[index] = {
      ...updatedItems[index],
      [field]: value,
    };
    
    if (field === 'qty' || field === 'price') {
      updatedItems[index].subtotal = updatedItems[index].qty * updatedItems[index].price;
    }
    
    setItems(updatedItems);
  };

  const calculateTotal = () => {
    return items.reduce((sum, item) => sum + item.subtotal, 0);
  };

  const onSubmit = async (data: OfflineSaleFormData) => {
    if (items.length === 0) {
      setError('Tambahkan minimal 1 item');
      return;
    }

    try {
      setIsLoading(true);
      setError('');

      const response = await salesApi.createOfflineSale({
        ...data,
        items: items.map(item => ({
          product_id: item.product_id,
          qty: item.qty,
          price: item.price,
        })),
      });

      if (response.success) {
        alert('Penjualan offline berhasil disimpan');
        router.push('/sales/list');
      } else {
        setError(response.message || 'Gagal menyimpan penjualan');
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
                  <h1 className="text-3xl font-bold tracking-tight">Penjualan Offline</h1>
                  <p className="text-muted-foreground">
                    Input transaksi penjualan langsung/offline
                  </p>
                </div>
                <Button
                  variant="outline"
                  onClick={() => router.push('/sales/choose-type')}
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
                    <CardTitle>Informasi Order</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="grid gap-4 md:grid-cols-2">
                      <div className="space-y-2">
                        <Label htmlFor="order_date">Tanggal Order *</Label>
                        <Input
                          id="order_date"
                          type="date"
                          {...register('order_date')}
                          disabled={isLoading}
                        />
                        {errors.order_date && (
                          <p className="text-sm text-red-600">{errors.order_date.message}</p>
                        )}
                      </div>

                      <div className="space-y-2">
                        <Label htmlFor="sj_number">No. Surat Jalan (Opsional)</Label>
                        <Input
                          id="sj_number"
                          type="text"
                          placeholder="Auto-generate jika kosong"
                          {...register('sj_number')}
                          disabled={isLoading}
                        />
                      </div>

                      <div className="space-y-2">
                        <Label htmlFor="customer_id">Customer ID (Opsional)</Label>
                        <Input
                          id="customer_id"
                          type="number"
                          placeholder="Kosongkan untuk guest"
                          {...register('customer_id', { valueAsNumber: true })}
                          disabled={isLoading}
                        />
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <div className="flex items-center justify-between">
                      <CardTitle>Item Penjualan</CardTitle>
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={addItem}
                        disabled={isLoading}
                      >
                        <Plus className="w-4 h-4 mr-2" />
                        Tambah Item
                      </Button>
                    </div>
                  </CardHeader>
                  <CardContent>
                    {items.length === 0 ? (
                      <div className="text-center py-8 text-muted-foreground">
                        Belum ada item. Klik "Tambah Item" untuk menambahkan.
                      </div>
                    ) : (
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead className="w-12">#</TableHead>
                            <TableHead>Product ID</TableHead>
                            <TableHead>Nama Produk</TableHead>
                            <TableHead className="w-24">Qty</TableHead>
                            <TableHead className="w-32">Harga</TableHead>
                            <TableHead className="w-32">Subtotal</TableHead>
                            <TableHead className="w-16"></TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {items.map((item, index) => (
                            <TableRow key={index}>
                              <TableCell>{index + 1}</TableCell>
                              <TableCell>
                                <Input
                                  type="number"
                                  value={item.product_id || ''}
                                  onChange={(e) => updateItem(index, 'product_id', parseInt(e.target.value) || 0)}
                                  placeholder="ID"
                                  disabled={isLoading}
                                />
                              </TableCell>
                              <TableCell>
                                <Input
                                  type="text"
                                  value={item.product_name}
                                  onChange={(e) => updateItem(index, 'product_name', e.target.value)}
                                  placeholder="Nama produk"
                                  disabled={isLoading}
                                />
                              </TableCell>
                              <TableCell>
                                <Input
                                  type="number"
                                  value={item.qty}
                                  onChange={(e) => updateItem(index, 'qty', parseInt(e.target.value) || 0)}
                                  min="1"
                                  disabled={isLoading}
                                />
                              </TableCell>
                              <TableCell>
                                <Input
                                  type="number"
                                  value={item.price}
                                  onChange={(e) => updateItem(index, 'price', parseFloat(e.target.value) || 0)}
                                  min="0"
                                  disabled={isLoading}
                                />
                              </TableCell>
                              <TableCell className="font-semibold">
                                {formatCurrency(item.subtotal)}
                              </TableCell>
                              <TableCell>
                                <Button
                                  type="button"
                                  variant="ghost"
                                  size="icon"
                                  onClick={() => removeItem(index)}
                                  disabled={isLoading}
                                >
                                  <Trash2 className="w-4 h-4 text-red-600" />
                                </Button>
                              </TableCell>
                            </TableRow>
                          ))}
                          <TableRow>
                            <TableCell colSpan={5} className="text-right font-bold">
                              Total:
                            </TableCell>
                            <TableCell className="font-bold text-lg">
                              {formatCurrency(calculateTotal())}
                            </TableCell>
                            <TableCell></TableCell>
                          </TableRow>
                        </TableBody>
                      </Table>
                    )}
                  </CardContent>
                </Card>

                <div className="flex justify-end gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => router.push('/sales/choose-type')}
                    disabled={isLoading}
                  >
                    Batal
                  </Button>
                  <Button type="submit" disabled={isLoading}>
                    <Save className="w-4 h-4 mr-2" />
                    {isLoading ? 'Menyimpan...' : 'Simpan Penjualan'}
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}
