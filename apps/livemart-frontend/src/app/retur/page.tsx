'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { RotateCcw, Package, Store } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function ReturPage() {
  const router = useRouter();

  const returTypes = [
    {
      title: 'Retur Pembelian',
      description: 'Retur barang ke supplier',
      icon: Package,
      color: 'bg-red-600',
      path: '/retur/pembelian',
    },
    {
      title: 'Retur Penjualan Online',
      description: 'Retur dari customer online (Shopee, Tiktok)',
      icon: RotateCcw,
      color: 'bg-orange-600',
      path: '/retur/penjualan',
    },
    {
      title: 'Retur Penjualan Offline',
      description: 'Retur dari customer offline',
      icon: Store,
      color: 'bg-yellow-600',
      path: '/retur/offline',
    },
  ];

  return (
    <div className="h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 flex overflow-hidden">
        <Sidebar />
        <main className="flex-1 overflow-y-auto bg-gray-50">
          <div className="container mx-auto p-6">
            <div className="space-y-6">
              <div>
                <h1 className="text-3xl font-bold tracking-tight">Retur Management</h1>
                <p className="text-muted-foreground">
                  Kelola retur pembelian dan penjualan
                </p>
              </div>

              <div className="grid gap-6 md:grid-cols-3">
                {returTypes.map((type) => {
                  const Icon = type.icon;
                  return (
                    <Card
                      key={type.path}
                      className="cursor-pointer hover:shadow-lg transition-shadow"
                      onClick={() => router.push(type.path)}
                    >
                      <CardHeader>
                        <div className="flex items-center gap-4">
                          <div className={`${type.color} p-3 rounded-lg`}>
                            <Icon className="w-6 h-6 text-white" />
                          </div>
                          <div className="flex-1">
                            <CardTitle className="text-lg">{type.title}</CardTitle>
                            <CardDescription className="mt-1">
                              {type.description}
                            </CardDescription>
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <Button
                          variant="outline"
                          className="w-full"
                          onClick={(e) => {
                            e.stopPropagation();
                            router.push(type.path);
                          }}
                        >
                          Buka
                        </Button>
                      </CardContent>
                    </Card>
                  );
                })}
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Informasi Retur</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3 text-sm text-muted-foreground">
                    <div>
                      <strong>Retur Pembelian:</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Return barang ke supplier karena rusak/cacat</li>
                        <li>Stock akan dikembalikan ke unlocated</li>
                        <li>Generate dokumen retur</li>
                      </ul>
                    </div>
                    <div>
                      <strong>Retur Penjualan Online:</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Customer return dari Shopee/Tiktok</li>
                        <li>Search order untuk diretur</li>
                        <li>Process refund via finance</li>
                        <li>Stock adjustment otomatis</li>
                      </ul>
                    </div>
                    <div>
                      <strong>Retur Penjualan Offline:</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Customer return offline</li>
                        <li>Process refund</li>
                        <li>Stock adjustment otomatis</li>
                      </ul>
                    </div>
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
