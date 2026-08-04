'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { DollarSign, CreditCard, FileText, AlertCircle } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function FinanceChoosePage() {
  const router = useRouter();

  const financeTypes = [
    {
      title: 'Finance Shopee Lamourad',
      description: 'Kelola pembayaran dan invoice Shopee Lamourad',
      icon: DollarSign,
      color: 'bg-orange-500',
      path: '/finance/shopee',
      platform: 'shopee',
    },
    {
      title: 'Finance Shopee Liefmarket',
      description: 'Kelola pembayaran dan invoice Shopee Liefmarket',
      icon: DollarSign,
      color: 'bg-orange-600',
      path: '/finance/shopee2',
      platform: 'shopee2',
    },
    {
      title: 'Finance Tiktok Lamourad',
      description: 'Kelola pembayaran dan invoice Tiktok Lamourad',
      icon: CreditCard,
      color: 'bg-black',
      path: '/finance/tiktok',
      platform: 'tiktok',
    },
    {
      title: 'Finance Tiktok Liefmarket',
      description: 'Kelola pembayaran dan invoice Tiktok Liefmarket',
      icon: CreditCard,
      color: 'bg-gray-800',
      path: '/finance/tiktok2',
      platform: 'tiktok2',
    },
    {
      title: 'Finance Offline',
      description: 'Kelola invoice penjualan offline',
      icon: FileText,
      color: 'bg-green-600',
      path: '/finance/offline',
      platform: 'offline',
    },
    {
      title: 'Unpaid Orders',
      description: 'Lihat order yang belum dibayar',
      icon: AlertCircle,
      color: 'bg-red-600',
      path: '/finance/unpaid-orders',
      platform: 'unpaid',
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
                <h1 className="text-3xl font-bold tracking-tight">Finance Management</h1>
                <p className="text-muted-foreground">
                  Kelola pembayaran, invoice, dan keuangan untuk semua platform
                </p>
              </div>

              <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {financeTypes.map((type) => {
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
                  <CardTitle>Informasi</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3 text-sm text-muted-foreground">
                    <div>
                      <strong>Finance Online (Shopee/Tiktok):</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Import data pembayaran dari Excel</li>
                        <li>Input manual pembayaran</li>
                        <li>Generate invoice (PKP/Non-PKP)</li>
                        <li>Lock/unlock transaksi</li>
                        <li>History dan adjustment</li>
                      </ul>
                    </div>
                    <div>
                      <strong>Finance Offline:</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Generate invoice untuk offline sales</li>
                        <li>Print invoice PKP/Non-PKP</li>
                      </ul>
                    </div>
                    <div>
                      <strong>Unpaid Orders:</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Tracking order yang belum dibayar</li>
                        <li>Filter per platform</li>
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
