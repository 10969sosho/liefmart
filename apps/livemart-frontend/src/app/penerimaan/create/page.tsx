'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery, useMutation } from '@tanstack/react-query';
import { ArrowLeft, Save, Plus, Trash2, Package, History, AlertCircle } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { penerimaanApi } from '@/lib/api';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

interface PenerimaanItem {
  product_id: number;
  product_name: string;
  qty: number;
  satuan_id: number;
  satuan_name: string;
  harga_hpp: number;
  diskon_persen_1: number;
  diskon_nominal_1: number;
  diskon_persen_2: number;
  diskon_nominal_2: number;
  diskon_persen_3: number;
  diskon_nominal_3: number;
  diskon_persen_4: number;
  diskon_nominal_4: number;
  diskon_persen_5: number;
  diskon_nominal_5: number;
  is_free: boolean;
  subtotal: number;
  catatan: string;
}

function PenerimaanCreateContent() {
  const router = useRouter();
  
  // Form State
  const [formData, setFormData] = React.useState({
    main_category_id: '',
    tax_category_id: '',
    nomor_po: '',
    tanggal_penerimaan: new Date().toISOString().split('T')[0],
    metode_pembayaran: 'Cash',
    tanggal_jatuh_tempo: '',
    catatan: '',
  });

  // Item Form State
  const [currentItem, setCurrentItem] = React.useState({
    product_id: 0,
    qty: 1,
    satuan_id: 0,
    harga_hpp: 0,
    diskon_persen_1: 0,
    diskon_nominal_1: 0,
    diskon_persen_2: 0,
    diskon_nominal_2: 0,
    diskon_persen_3: 0,
    diskon_nominal_3: 0,
    diskon_persen_4: 0,
    diskon_nominal_4: 0,
    diskon_persen_5: 0,
    diskon_nominal_5: 0,
    is_free: false,
    catatan: '',
  });

  const [items, setItems] = React.useState<PenerimaanItem[]>([]);
  const [isSubmitting, setIsSubmitting] = React.useState(false);
  const [error, setError] = React.useState('');

  // Fetch master data
  const { data: mainCategories } = useQuery({
    queryKey: ['main-categories'],
    queryFn: () => penerimaanApi.getMainCategories(),
  });

  const { data: taxCategories } = useQuery({
    queryKey: ['tax-categories', formData.main_category_id],
    queryFn: () => penerimaanApi.getTaxCategories(parseInt(formData.main_category_id)),
    enabled: !!formData.main_category_id,
  });

  const { data: products } = useQuery({
    queryKey: ['products', formData.main_category_id],
    queryFn: () => penerimaanApi.getProducts({ main_category_id: parseInt(formData.main_category_id) }),
    enabled: !!formData.main_category_id,
  });

  const { data: satuans } = useQuery({
    queryKey: ['satuans'],
    queryFn: () => penerimaanApi.getSatuans(),
  });

  // Calculate subtotal with cascading discounts
  const calculateSubtotal = (item: typeof currentItem) => {
    if (item.is_free) return 0;
    
    let subtotal = item.qty * item.harga_hpp;
    
    // Apply 5 levels of cascading discounts
    for (let i = 1; i <= 5; i++) {
      const persen = item[`diskon_persen_${i}` as keyof typeof item] as number || 0;
      const nominal = item[`diskon_nominal_${i}` as keyof typeof item] as number || 0;
      
      if (persen > 0) {
        subtotal = subtotal - (subtotal * persen / 100);
      } else if (nominal > 0) {
        subtotal = subtotal - nominal;
      }
    }
    
    return Math.max(0, subtotal);
  };

  // Add item to list
  const handleAddItem = () => {
    if (!currentItem.product_id || !currentItem.satuan_id || currentItem.qty <= 0) {
      alert('Lengkapi data produk, qty, dan satuan');
      return;
    }

    const product = products?.data?.find((p: any) => p.id === currentItem.product_id);
    const satuan = satuans?.data?.find((s: any) => s.id === currentItem.satuan_id);

    if (!product || !satuan) return;

    const newItem: PenerimaanItem = {
      ...currentItem,
      product_name: product.name,
      satuan_name: satuan.name,
      subtotal: calculateSubtotal(currentItem),
    };

    setItems([...items, newItem]);
    
    // Reset form
    setCurrentItem({
      product_id: 0,
      qty: 1,
      satuan_id: 0,
      harga_hpp: 0,
      diskon_persen_1: 0,
      diskon_nominal_1: 0,
      diskon_persen_2: 0,
      diskon_nominal_2: 0,
      diskon_persen_3: 0,
      diskon_nominal_3: 0,
      diskon_persen_4: 0,
      diskon_nominal_4: 0,
      diskon_persen_5: 0,
      diskon_nominal_5: 0,
      is_free: false,
      catatan: '',
    });
  };

  // Remove item
  const handleRemoveItem = (index: number) => {
    setItems(items.filter((_, i) => i !== index));
  };

  // Calculate grand total
  const grandTotal = items.reduce((sum, item) => sum + item.subtotal, 0);

  // Submit form
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.main_category_id || !formData.tax_category_id || !formData.nomor_po) {
      setError('Lengkapi semua field yang wajib');
      return;
    }

    if (items.length === 0) {
      setError('Tambahkan minimal 1 item');
      return;
    }

    try {
      setIsSubmitting(true);
      setError('');

      // Step 1: Create header
      const headerResponse = await penerimaanApi.createHeader({
        main_category_id: parseInt(formData.main_category_id),
        tax_category_id: parseInt(formData.tax_category_id),
        nomor_po: formData.nomor_po,
        tanggal_penerimaan: formData.tanggal_penerimaan,
        metode_pembayaran: formData.metode_pembayaran as 'Cash' | 'Jatuh Tempo',
        tanggal_jatuh_tempo: formData.metode_pembayaran === 'Jatuh Tempo' ? formData.tanggal_jatuh_tempo : undefined,
        catatan: formData.catatan,
      });

      if (!headerResponse.success) {
        throw new Error(headerResponse.message || 'Gagal membuat header');
      }

      const penerimaanId = headerResponse.data.id;

      // Step 2: Store items
      const itemsResponse = await penerimaanApi.storeBatchDetails(penerimaanId, items);

      if (!itemsResponse.success) {
        throw new Error(itemsResponse.message || 'Gagal menyimpan detail items');
      }

      // Success - redirect
      router.push('/penerimaan');
    } catch (err: any) {
      setError(err.message || 'Terjadi kesalahan saat menyimpan');
    } finally {
      setIsSubmitting(false);
    }
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(value);
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100/50">
      <Navbar />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-8">
          <div className="max-w-7xl mx-auto space-y-6">
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
                  <h1 className="text-3xl font-bold text-gray-900">Tambah Penerimaan</h1>
                  <p className="text-gray-500 mt-1">Buat penerimaan barang baru dari supplier</p>
                </div>
              </div>
            </div>

            {error && (
              <div className="bg-red-50 border-2 border-red-200 rounded-xl p-4 flex items-start gap-3 animate-fade-in">
                <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                <div className="flex-1">
                  <p className="text-sm font-semibold text-red-900">Error</p>
                  <p className="text-sm text-red-700 mt-1">{error}</p>
                </div>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Informasi Utama */}
              <Card>
                <CardHeader>
                  <CardTitle>Informasi Utama</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Kategori Barang <span className="text-red-600">*</span>
                      </label>
                      <select
                        value={formData.main_category_id}
                        onChange={(e) => {
                          setFormData({ ...formData, main_category_id: e.target.value, tax_category_id: '' });
                          setItems([]);
                        }}
                        className="input-modern"
                        required
                        disabled={isSubmitting}
                      >
                        <option value="">-- Pilih Kategori --</option>
                        {mainCategories?.data?.map((cat: any) => (
                          <option key={cat.id} value={cat.id}>{cat.name}</option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Kategori Pajak <span className="text-red-600">*</span>
                      </label>
                      <select
                        value={formData.tax_category_id}
                        onChange={(e) => setFormData({ ...formData, tax_category_id: e.target.value })}
                        className="input-modern"
                        required
                        disabled={!formData.main_category_id || isSubmitting}
                      >
                        <option value="">-- Pilih Kategori Pajak --</option>
                        {taxCategories?.data?.map((cat: any) => (
                          <option key={cat.id} value={cat.id}>{cat.name}</option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Nomor PO <span className="text-red-600">*</span>
                      </label>
                      <Input
                        type="text"
                        value={formData.nomor_po}
                        onChange={(e) => setFormData({ ...formData, nomor_po: e.target.value })}
                        placeholder="Masukkan nomor PO"
                        required
                        disabled={isSubmitting}
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Penerimaan <span className="text-red-600">*</span>
                      </label>
                      <Input
                        type="date"
                        value={formData.tanggal_penerimaan}
                        onChange={(e) => setFormData({ ...formData, tanggal_penerimaan: e.target.value })}
                        required
                        disabled={isSubmitting}
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Metode Pembayaran <span className="text-red-600">*</span>
                      </label>
                      <select
                        value={formData.metode_pembayaran}
                        onChange={(e) => setFormData({ ...formData, metode_pembayaran: e.target.value })}
                        className="input-modern"
                        required
                        disabled={isSubmitting}
                      >
                        <option value="Cash">Cash</option>
                        <option value="Jatuh Tempo">Jatuh Tempo</option>
                      </select>
                    </div>

                    {formData.metode_pembayaran === 'Jatuh Tempo' && (
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Tanggal Jatuh Tempo <span className="text-red-600">*</span>
                        </label>
                        <Input
                          type="date"
                          value={formData.tanggal_jatuh_tempo}
                          onChange={(e) => setFormData({ ...formData, tanggal_jatuh_tempo: e.target.value })}
                          required
                          disabled={isSubmitting}
                        />
                      </div>
                    )}

                    <div className="col-span-2">
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Catatan
                      </label>
                      <textarea
                        value={formData.catatan}
                        onChange={(e) => setFormData({ ...formData, catatan: e.target.value })}
                        rows={3}
                        className="input-modern"
                        placeholder="Catatan tambahan (opsional)"
                        disabled={isSubmitting}
                      />
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Form Tambah Item */}
              {formData.main_category_id && (
                <Card>
                  <CardHeader>
                    <CardTitle>Tambah Item Barang</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-6">
                      {/* Basic Info */}
                      <div className="grid grid-cols-4 gap-4">
                        <div className="col-span-2">
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Nama Barang <span className="text-red-600">*</span>
                          </label>
                          <select
                            value={currentItem.product_id}
                            onChange={(e) => {
                              const productId = parseInt(e.target.value);
                              const product = products?.data?.find((p: any) => p.id === productId);
                              setCurrentItem({
                                ...currentItem,
                                product_id: productId,
                                harga_hpp: product?.price || 0,
                                satuan_id: product?.default_satuan_id || 0,
                              });
                            }}
                            className="input-modern"
                            disabled={isSubmitting}
                          >
                            <option value="0">-- Pilih Barang --</option>
                            {products?.data?.map((product: any) => (
                              <option key={product.id} value={product.id}>{product.name}</option>
                            ))}
                          </select>
                        </div>

                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Qty <span className="text-red-600">*</span>
                          </label>
                          <Input
                            type="number"
                            value={currentItem.qty}
                            onChange={(e) => setCurrentItem({ ...currentItem, qty: parseFloat(e.target.value) || 0 })}
                            min="0.01"
                            step="0.01"
                            disabled={isSubmitting}
                          />
                        </div>

                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Satuan <span className="text-red-600">*</span>
                          </label>
                          <select
                            value={currentItem.satuan_id}
                            onChange={(e) => setCurrentItem({ ...currentItem, satuan_id: parseInt(e.target.value) })}
                            className="input-modern"
                            disabled={isSubmitting}
                          >
                            <option value="0">-- Pilih Satuan --</option>
                            {satuans?.data?.map((satuan: any) => (
                              <option key={satuan.id} value={satuan.id}>{satuan.name}</option>
                            ))}
                          </select>
                        </div>
                      </div>

                      {/* Price */}
                      <div className="grid grid-cols-4 gap-4">
                        <div className="col-span-2">
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Harga HPP <span className="text-red-600">*</span>
                          </label>
                          <div className="relative">
                            <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                              Rp
                            </span>
                            <Input
                              type="number"
                              value={currentItem.harga_hpp}
                              onChange={(e) => setCurrentItem({ ...currentItem, harga_hpp: parseFloat(e.target.value) || 0 })}
                              className="pl-12"
                              min="0"
                              disabled={isSubmitting || currentItem.is_free}
                            />
                          </div>
                        </div>

                        <div className="col-span-2 flex items-end">
                          <label className="flex items-center gap-2 cursor-pointer">
                            <input
                              type="checkbox"
                              checked={currentItem.is_free}
                              onChange={(e) => setCurrentItem({ ...currentItem, is_free: e.target.checked })}
                              className="w-4 h-4 rounded border-gray-300"
                              disabled={isSubmitting}
                            />
                            <span className="text-sm font-medium text-gray-700">Barang Free (Gratis)</span>
                          </label>
                        </div>
                      </div>

                      {/* 5 Level Discounts */}
                      <div className="p-4 bg-blue-50 rounded-xl border border-blue-200">
                        <div className="mb-3">
                          <h3 className="font-semibold text-gray-900 flex items-center gap-2">
                            <Package className="w-4 h-4" />
                            Sistem Diskon (5 Level)
                          </h3>
                          <p className="text-xs text-gray-600 mt-1">Diskon diterapkan secara bertingkat. Isi hanya satu jenis per level (% atau Rp)</p>
                        </div>

                        <div className="grid grid-cols-10 gap-3">
                          {[1, 2, 3, 4, 5].map((level) => (
                            <React.Fragment key={level}>
                              <div className="col-span-2">
                                <label className="block text-xs font-medium text-gray-700 mb-1">
                                  Diskon {level} (%)
                                </label>
                                <div className="relative">
                                  <Input
                                    type="number"
                                    value={currentItem[`diskon_persen_${level}` as keyof typeof currentItem] as number || ''}
                                    onChange={(e) => setCurrentItem({
                                      ...currentItem,
                                      [`diskon_persen_${level}`]: parseFloat(e.target.value) || 0,
                                      [`diskon_nominal_${level}`]: 0,
                                    })}
                                    className="text-center pr-6"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="0"
                                    disabled={isSubmitting || currentItem.is_free}
                                  />
                                  <span className="absolute right-2 top-1/2 transform -translate-y-1/2 text-xs text-gray-500">%</span>
                                </div>
                              </div>
                            </React.Fragment>
                          ))}

                          {[1, 2, 3, 4, 5].map((level) => (
                            <React.Fragment key={`nominal-${level}`}>
                              <div className="col-span-2">
                                <label className="block text-xs font-medium text-gray-700 mb-1">
                                  Nominal {level}
                                </label>
                                <div className="relative">
                                  <span className="absolute left-2 top-1/2 transform -translate-y-1/2 text-xs text-gray-500">Rp</span>
                                  <Input
                                    type="number"
                                    value={currentItem[`diskon_nominal_${level}` as keyof typeof currentItem] as number || ''}
                                    onChange={(e) => setCurrentItem({
                                      ...currentItem,
                                      [`diskon_nominal_${level}`]: parseFloat(e.target.value) || 0,
                                      [`diskon_persen_${level}`]: 0,
                                    })}
                                    className="pl-8"
                                    min="0"
                                    placeholder="0"
                                    disabled={isSubmitting || currentItem.is_free}
                                  />
                                </div>
                              </div>
                            </React.Fragment>
                          ))}
                        </div>
                      </div>

                      {/* Catatan Item */}
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Catatan Item
                        </label>
                        <Input
                          type="text"
                          value={currentItem.catatan}
                          onChange={(e) => setCurrentItem({ ...currentItem, catatan: e.target.value })}
                          placeholder="Catatan untuk item ini (opsional)"
                          disabled={isSubmitting}
                        />
                      </div>

                      {/* Subtotal & Add Button */}
                      <div className="flex items-center justify-between pt-4 border-t">
                        <div>
                          <p className="text-sm text-gray-600">Subtotal Item:</p>
                          <p className="text-2xl font-bold text-gray-900">
                            {formatCurrency(calculateSubtotal(currentItem))}
                          </p>
                        </div>
                        <Button
                          type="button"
                          onClick={handleAddItem}
                          disabled={isSubmitting}
                        >
                          <Plus className="w-4 h-4 mr-2" />
                          Tambah ke Daftar
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}

              {/* Items List */}
              {items.length > 0 && (
                <Card>
                  <CardHeader>
                    <CardTitle>Daftar Item ({items.length})</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {items.map((item, index) => (
                        <div
                          key={index}
                          className="p-4 bg-gray-50 rounded-xl border border-gray-200 flex items-start justify-between gap-4"
                        >
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 mb-1">
                              <span className="text-sm font-bold text-gray-500">#{index + 1}</span>
                              <h4 className="font-bold text-gray-900">{item.product_name}</h4>
                              {item.is_free && <Badge variant="success">FREE</Badge>}
                            </div>
                            <div className="grid grid-cols-4 gap-4 text-sm mt-2">
                              <div>
                                <span className="text-gray-500">Qty:</span>
                                <span className="font-medium text-gray-900 ml-2">{item.qty} {item.satuan_name}</span>
                              </div>
                              <div>
                                <span className="text-gray-500">Harga:</span>
                                <span className="font-medium text-gray-900 ml-2">{formatCurrency(item.harga_hpp)}</span>
                              </div>
                              <div>
                                <span className="text-gray-500">Diskon:</span>
                                <span className="font-medium text-orange-600 ml-2">
                                  {[1,2,3,4,5].map(i => item[`diskon_persen_${i}` as keyof PenerimaanItem] || 0).reduce((a,b) => a+b, 0) > 0 
                                    ? `${[1,2,3,4,5].map(i => item[`diskon_persen_${i}` as keyof PenerimaanItem] || 0).reduce((a,b) => a+b, 0)}%`
                                    : '-'}
                                </span>
                              </div>
                              <div>
                                <span className="text-gray-500">Subtotal:</span>
                                <span className="font-bold text-gray-900 ml-2">{formatCurrency(item.subtotal)}</span>
                              </div>
                            </div>
                            {item.catatan && (
                              <p className="text-xs text-gray-600 mt-2 italic">Catatan: {item.catatan}</p>
                            )}
                          </div>
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={() => handleRemoveItem(index)}
                            disabled={isSubmitting}
                            className="flex-shrink-0"
                          >
                            <Trash2 className="w-4 h-4 text-red-600" />
                          </Button>
                        </div>
                      ))}
                    </div>

                    {/* Grand Total */}
                    <div className="mt-6 pt-6 border-t border-gray-300">
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="text-sm text-gray-600">Total Keseluruhan</p>
                          <p className="text-xs text-gray-500">{items.length} item</p>
                        </div>
                        <div className="text-right">
                          <p className="text-3xl font-bold text-blue-600">{formatCurrency(grandTotal)}</p>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}

              {/* Submit Button */}
              <div className="flex justify-end gap-3">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => router.push('/penerimaan')}
                  disabled={isSubmitting}
                >
                  Batal
                </Button>
                <Button
                  type="submit"
                  disabled={isSubmitting || items.length === 0}
                  className="min-w-[200px]"
                >
                  <Save className="w-4 h-4 mr-2" />
                  {isSubmitting ? 'Menyimpan...' : 'Simpan Penerimaan'}
                </Button>
              </div>
            </form>
          </div>
        </main>
      </div>
    </div>
  );
}

export default function PenerimaanCreatePage() {
  return (
    <QueryClientProvider client={queryClient}>
      <PenerimaanCreateContent />
    </QueryClientProvider>
  );
}
