'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { Plus, Search, Eye, Edit, Trash2, GitBranch } from 'lucide-react';
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

function MappingContent() {
  const router = useRouter();
  const [search, setSearch] = React.useState('');
  const [platform, setPlatform] = React.useState('');

  const mappings = [
    { id: 1, platform: 'shopee', platform_product: 'Lamourad Tumbler 500ml', internal_product: 'Tumbler SS 500ml', product_id: 101 },
    { id: 2, platform: 'tiktok', platform_product: 'LMR Water Bottle', internal_product: 'Botol Minum 1L', product_id: 102 },
    { id: 3, platform: 'shopee2', platform_product: 'Liefmarket Tumbler', internal_product: 'Tumbler SS 500ml', product_id: 101 },
  ];

  const filtered = mappings.filter(m => 
    (!platform || m.platform === platform) &&
    (!search || m.platform_product.toLowerCase().includes(search.toLowerCase()) || m.internal_product.toLowerCase().includes(search.toLowerCase()))
  );

  const platformColors: Record<string, string> = {
    shopee: 'bg-orange-100 text-orange-800',
    shopee2: 'bg-orange-200 text-orange-900',
    tiktok: 'bg-gray-800 text-white',
    tiktok2: 'bg-gray-600 text-white',
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
                  <h1 className="text-3xl font-bold tracking-tight">Product Mapping</h1>
                  <p className="text-muted-foreground">
                    Mapping produk platform ke produk internal
                  </p>
                </div>
                <div className="flex gap-2">
                  <Button variant="default">
                    <Plus className="w-4 h-4 mr-2" />
                    Auto Mapping
                  </Button>
                  <Button variant="outline" onClick={() => router.push('/master')}>
                    Kembali
                  </Button>
                </div>
              </div>

              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between flex-wrap gap-4">
                    <CardTitle>Daftar Mapping</CardTitle>
                    <div className="flex items-center gap-2">
                      <div className="relative">
                        <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                          placeholder="Cari mapping..."
                          value={search}
                          onChange={(e) => setSearch(e.target.value)}
                          className="pl-8 w-64"
                        />
                      </div>
                      <select
                        value={platform}
                        onChange={(e) => setPlatform(e.target.value)}
                        className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                      >
                        <option value="">Semua Platform</option>
                        <option value="shopee">Shopee Lamourad</option>
                        <option value="shopee2">Shopee Liefmarket</option>
                        <option value="tiktok">Tiktok Lamourad</option>
                        <option value="tiktok2">Tiktok Liefmarket</option>
                      </select>
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Platform</TableHead>
                        <TableHead>Produk Platform</TableHead>
                        <TableHead>
                          <GitBranch className="w-4 h-4 inline mr-1" />
                          Produk Internal
                        </TableHead>
                        <TableHead>Product ID</TableHead>
                        <TableHead className="text-right">Aksi</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {filtered.map((mapping) => (
                        <TableRow key={mapping.id}>
                          <TableCell>
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${platformColors[mapping.platform] || 'bg-gray-100 text-gray-800'}`}>
                              {mapping.platform}
                            </span>
                          </TableCell>
                          <TableCell className="font-medium">{mapping.platform_product}</TableCell>
                          <TableCell>{mapping.internal_product}</TableCell>
                          <TableCell>{mapping.product_id}</TableCell>
                          <TableCell className="text-right">
                            <div className="flex items-center justify-end gap-2">
                              <Button variant="ghost" size="icon">
                                <Eye className="w-4 h-4" />
                              </Button>
                              <Button variant="ghost" size="icon">
                                <Edit className="w-4 h-4" />
                              </Button>
                              <Button variant="ghost" size="icon">
                                <Trash2 className="w-4 h-4 text-red-600" />
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </CardContent>
              </Card>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}

export default function MappingPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <MappingContent />
    </QueryClientProvider>
  );
}
