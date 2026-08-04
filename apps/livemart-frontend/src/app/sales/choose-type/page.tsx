'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { ShoppingBag, Store, List, TrendingUp } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function SalesChooseTypePage() {
  const router = useRouter();

  const salesTypes = [
    {
      title: 'Penjualan Online',
      description: 'Kelola penjualan dari platform e-commerce (Shopee, Tiktok)',
      icon: ShoppingBag,
      color: 'bg-blue-500',
      path: '/sales/online',
    },
    {
      title: 'Penjualan Offline',
      description: 'Input dan kelola penjualan langsung/offline',
      icon: Store,
      color: 'bg-green-500',
      path: '/sales/offline',
    },
    {
      title: 'List Semua Penjualan',
      description: 'Lihat dan kelola semua transaksi penjualan',
      icon: List,
      color: 'bg-purple-500',
      path: '/sales/list',
    },
    {
      title: 'Barang Keluar',
      description: 'Lihat daftar barang keluar dari gudang',
      icon: TrendingUp,
      color: 'bg-orange-500',
      path: '/sales/outgoing-items',
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
                <h1 className="text-3xl font-bold tracking-tight">Sales Management</h1>
                <p className="text-muted-foreground">
                  Pilih jenis penjualan yang ingin Anda kelola
                </p>
              </div>

              <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-2">
                {salesTypes.map((type) => {
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
                            <CardTitle>{type.title}</CardTitle>
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
                  <CardTitle>Informasi</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2 text-sm text-muted-foreground">
                    <p>
                      <strong>Penjualan Online:</strong> Untuk import data dari Excel (Shopee, Shopee2, Tiktok, Tiktok2) atau input manual
                    </p>
                    <p>
                      <strong>Penjualan Offline:</strong> Untuk transaksi langsung dengan customer
                    </p>
                    <p>
                      <strong>List Semua Penjualan:</strong> Melihat semua transaksi dari semua platform
                    </p>
                    <p>
                      <strong>Barang Keluar:</strong> Tracking barang yang sudah keluar dari gudang
                    </p>
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
