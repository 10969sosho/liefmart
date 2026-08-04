'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Save, ArrowLeft, Plus, Trash2, Package } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';

const penerimaanSchema = z.object({
  date: z.string().min(1, 'Tanggal wajib diisi'),
  supplier_name: z.string().optional(),
  notes: z.string().optional(),
});

type PenerimaanFormData = z.infer<typeof penerimaanSchema>;

interface PenerimaanItem {
  product_id: number;
  product_name: string;
  qty: number;
  price: number;
  subtotal: number;
}

export default function PenerimaanCreatePage() {
  const router = useRouter();
  const [items, setItems] = React.useState<PenerimaanItem[]>([]);
  const [isLoading, setIsLoading] = React.useState(false);
  const [error, setError] = React.useState('');

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<PenerimaanFormData>({
    resolver: zodResolver(penerimaanSchema),
    defaultValues: {
      date: new Date().toISOString().split('T')[0],
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

  const updateItem = (index: number, field: keyof PenerimaanItem, value: any) => {
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

  const onSubmit = async (data: PenerimaanFormData) => {
    if (items.length === 0) {
      setError('Tambahkan minimal 1 item');
      return;
    }

    try {
      setIsLoading(true);
      setError('');
      
      // TODO: Call API
      alert('Penerimaan berhasil disimpan (mock)');
      router.push('/penerimaan');
    } catch (err: any) {
      setError(err.message || 'Terjadi kesalahan');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-8">
          <div className="max-w-5xl mx-auto">
            <div className="mb-6">
              <button
                onClick={() => router.push('/penerimaan')}
                className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 mb-4"
              >
                <ArrowLeft className="w-4 h-4" />
                Kembali
              </button>
              <h1 className="text-3xl font-bold text-gray-900">Tambah Penerimaan</h1>
              <p className="text-gray-500 mt-1">Buat penerimaan barang baru dari supplier</p>
            </div>

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
              {error && (
                <div className="bg-red-50 border border-red-200 text-red-600 p-4 rounded-xl text-sm">
                  {error}
                </div>
              )}

              {/* Info Section */}
              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Informasi Penerimaan</h2>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Tanggal *
                    </label>
                    <input
                      type="date"
                      {...register('date')}
                      className="input-modern"
                      disabled={isLoading}
                    />
                    {errors.date && (
                      <p className="text-sm text-red-600 mt-1">{errors.date.message}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Supplier
                    </label>
                    <input
                      type="text"
                      {...register('supplier_name')}
                      className="input-modern"
                      placeholder="Nama supplier"
                      disabled={isLoading}
                    />
                  </div>

                  <div className="col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Catatan
                    </label>
                    <textarea
                      {...register('notes')}
                      rows={3}
                      className="input-modern"
                      placeholder="Catatan tambahan (opsional)"
                      disabled={isLoading}
                    />
                  </div>
                </div>
              </div>

              {/* Items Section */}
              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                  <h2 className="text-lg font-semibold text-gray-900">Item Penerimaan</h2>
                  <button
                    type="button"
                    onClick={addItem}
                    className="btn-modern bg-gradient-to-r from-blue-600 to-purple-600 text-white flex items-center gap-2"
                  >
                    <Plus className="w-4 h-4" />
                    Tambah Item
                  </button>
                </div>

                {items.length === 0 ? (
                  <div className="text-center py-12">
                    <Package className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p className="text-gray-500">Belum ada item</p>
                    <p className="text-sm text-gray-400 mt-1">Klik "Tambah Item" untuk menambahkan</p>
                  </div>
                ) : (
                  <div className="space-y-3">
                    {items.map((item, index) => (
                      <div key={index} className="grid grid-cols-12 gap-3 p-4 bg-gray-50 rounded-xl">
                        <div className="col-span-1 flex items-center justify-center">
                          <span className="text-sm font-semibold text-gray-500">{index + 1}</span>
                        </div>
                        <div className="col-span-4">
                          <input
                            type="text"
                            value={item.product_name}
                            onChange={(e) => updateItem(index, 'product_name', e.target.value)}
                            placeholder="Nama produk"
                            className="input-modern"
                            disabled={isLoading}
                          />
                        </div>
                        <div className="col-span-2">
                          <input
                            type="number"
                            value={item.qty}
                            onChange={(e) => updateItem(index, 'qty', parseInt(e.target.value) || 0)}
                            placeholder="Qty"
                            min="1"
                            className="input-modern"
                            disabled={isLoading}
                          />
                        </div>
                        <div className="col-span-2">
                          <input
                            type="number"
                            value={item.price}
                            onChange={(e) => updateItem(index, 'price', parseFloat(e.target.value) || 0)}
                            placeholder="Harga"
                            min="0"
                            className="input-modern"
                            disabled={isLoading}
                          />
                        </div>
                        <div className="col-span-2 flex items-center">
                          <span className="text-sm font-semibold text-gray-900">
                            Rp {item.subtotal.toLocaleString('id-ID')}
                          </span>
                        </div>
                        <div className="col-span-1 flex items-center justify-center">
                          <button
                            type="button"
                            onClick={() => removeItem(index)}
                            className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            disabled={isLoading}
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    ))}

                    {/* Total */}
                    <div className="flex justify-end pt-4 border-t border-gray-200">
                      <div className="text-right">
                        <p className="text-sm text-gray-500 mb-1">Total</p>
                        <p className="text-2xl font-bold text-gray-900">
                          Rp {calculateTotal().toLocaleString('id-ID')}
                        </p>
                      </div>
                    </div>
                  </div>
                )}
              </div>

              {/* Actions */}
              <div className="flex justify-end gap-3">
                <button
                  type="button"
                  onClick={() => router.push('/penerimaan')}
                  className="btn-modern border border-gray-300 text-gray-700 hover:bg-gray-50"
                  disabled={isLoading}
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={isLoading}
                  className="btn-modern bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg hover:shadow-xl disabled:opacity-50 flex items-center gap-2"
                >
                  <Save className="w-4 h-4" />
                  {isLoading ? 'Menyimpan...' : 'Simpan Penerimaan'}
                </button>
              </div>
            </form>
          </div>
        </main>
      </div>
    </div>
  );
}