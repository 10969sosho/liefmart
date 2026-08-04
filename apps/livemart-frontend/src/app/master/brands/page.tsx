'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { Plus, Search, Eye, Edit, Trash2, Tag } from 'lucide-react';
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

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

function BrandsContent() {
  const router = useRouter();
  const [search, setSearch] = React.useState('');
  const [activeTab, setActiveTab] = React.useState<'brands' | 'categories'>('brands');

  const brands = [
    { id: 1, name: 'Lamourad', sub_brands: 3 },
    { id: 2, name: 'Liefmarket', sub_brands: 2 },
    { id: 3, name: 'Internal Brand', sub_brands: 1 },
  ];

  const categories = [
    { id: 1, name: 'Tumbler', brand: 'Lamourad', types: 5 },
    { id: 2, name: 'Botol Minum', brand: 'Lamourad', types: 3 },
    { id: 3, name: 'Wadah Makanan', brand: 'Liefmarket', types: 2 },
  ];

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
                  <h1 className="text-3xl font-bold tracking-tight">Brands & Categories</h1>
                  <p className="text-muted-foreground">
                    Kelola brand, sub-brand, dan kategori produk
                  </p>
                </div>
                <div className="flex gap-2">
                  <Button variant="default">
                    <Plus className="w-4 h-4 mr-2" />
                    Tambah {activeTab === 'brands' ? 'Brand' : 'Category'}
                  </Button>
                  <Button variant="outline" onClick={() => router.push('/master')}>
                    Kembali
                  </Button>
                </div>
              </div>

              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between flex-wrap gap-4">
                    <div className="flex gap-2">
                      <Button
                        variant={activeTab === 'brands' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => setActiveTab('brands')}
                      >
                        <Tag className="w-4 h-4 mr-2" />
                        Brands
                      </Button>
                      <Button
                        variant={activeTab === 'categories' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => setActiveTab('categories')}
                      >
                        <Tag className="w-4 h-4 mr-2" />
                        Categories
                      </Button>
                    </div>
                    <div className="relative">
                      <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                      <Input
                        placeholder={`Cari ${activeTab}...`}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-8 w-64"
                      />
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  {activeTab === 'brands' ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>ID</TableHead>
                          <TableHead>Brand Name</TableHead>
                          <TableHead>Sub Brands</TableHead>
                          <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {brands.map((brand) => (
                          <TableRow key={brand.id}>
                            <TableCell>{brand.id}</TableCell>
                            <TableCell className="font-medium">{brand.name}</TableCell>
                            <TableCell>{brand.sub_brands} sub-brands</TableCell>
                            <TableCell className="text-right">
                              <div className="flex items-center justify-end gap-2">
                                <Button variant="ghost" size="icon"><Eye className="w-4 h-4" /></Button>
                                <Button variant="ghost" size="icon"><Edit className="w-4 h-4" /></Button>
                                <Button variant="ghost" size="icon"><Trash2 className="w-4 h-4 text-red-600" /></Button>
                              </div>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>ID</TableHead>
                          <TableHead>Category Name</TableHead>
                          <TableHead>Brand</TableHead>
                          <TableHead>Types</TableHead>
                          <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {categories.map((cat) => (
                          <TableRow key={cat.id}>
                            <TableCell>{cat.id}</TableCell>
                            <TableCell className="font-medium">{cat.name}</TableCell>
                            <TableCell>{cat.brand}</TableCell>
                            <TableCell>{cat.types} types</TableCell>
                            <TableCell className="text-right">
                              <div className="flex items-center justify-end gap-2">
                                <Button variant="ghost" size="icon"><Eye className="w-4 h-4" /></Button>
                                <Button variant="ghost" size="icon"><Edit className="w-4 h-4" /></Button>
                                <Button variant="ghost" size="icon"><Trash2 className="w-4 h-4 text-red-600" /></Button>
                              </div>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
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

export default function BrandsPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrandsContent />
    </QueryClientProvider>
  );
}
