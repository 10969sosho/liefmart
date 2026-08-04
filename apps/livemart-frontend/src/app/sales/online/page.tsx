'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { Upload, Plus } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function SalesOnlinePage() {
  const router = useRouter();

  const platforms = [
    {
      name: 'Shopee Lamourad',
      key: 'shopee',
      color: 'bg-orange-500',
      description: 'Import atau input manual penjualan Shopee Lamourad',
    },
    {
      name: 'Shopee Liefmarket',
      key: 'shopee2',
      color: 'bg-orange-600',
      description: 'Import atau input manual penjualan Shopee Liefmarket',
    },
    {
      name: 'Tiktok Lamourad',
      key: 'tiktok',
      color: 'bg-black',
      description: 'Import atau input manual penjualan Tiktok Lamourad',
    },
    {
      name: 'Tiktok Liefmarket',
      key: 'tiktok2',
      color: 'bg-gray-800',
      description: 'Import atau input manual penjualan Tiktok Liefmarket',
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
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-3xl font-bold tracking-tight">Penjualan Online</h1>
                  <p className="text-muted-foreground">
                    Pilih platform untuk import atau input manual penjualan
                  </p>
                </div>
                <Button
                  variant="outline"
                  onClick={() => router.push('/sales/choose-type')}
                >
                  Kembali
                </Button>
              </div>

              <div className="grid gap-6 md:grid-cols-2">
                {platforms.map((platform) => (
                  <Card key={platform.key}>
                    <CardHeader>
                      <div className="flex items-center gap-3">
                        <div className={`${platform.color} w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold`}>
                          {platform.name.substring(0, 1)}
                        </div>
                        <div>
                          <CardTitle>{platform.name}</CardTitle>
                          <CardDescription className="mt-1">
                            {platform.description}
                          </CardDescription>
                        </div>
                      </div>
                    </CardHeader>
                    <CardContent>
                      <div className="flex gap-2">
                        <Button
                          variant="default"
                          className="flex-1"
                          onClick={() => router.push(`/sales/online/${platform.key}/import`)}
                        >
                          <Upload className="w-4 h-4 mr-2" />
                          Import Excel
                        </Button>
                        <Button
                          variant="outline"
                          className="flex-1"
                          onClick={() => router.push(`/sales/online/${platform.key}/manual`)}
                        >
                          <Plus className="w-4 h-4 mr-2" />
                          Input Manual
                        </Button>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Petunjuk Import Excel</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3 text-sm text-muted-foreground">
                    <div>
                      <strong>Shopee:</strong>
                      <ol className="list-decimal list-inside ml-2 mt-1 space-y-1">
                        <li>Download template Excel dari sistem Shopee</li>
                        <li>Pastikan format sesuai dengan template</li>
                        <li>Upload file Excel</li>
                        <li>Preview data sebelum import</li>
                        <li>Konfirmasi import</li>
                      </ol>
                    </div>
                    <div>
                      <strong>Tiktok:</strong>
                      <ol className="list-decimal list-inside ml-2 mt-1 space-y-1">
                        <li>Download template Excel dari sistem Tiktok</li>
                        <li>Pastikan format sesuai dengan template</li>
                        <li>Upload file Excel</li>
                        <li>Preview data sebelum import</li>
                        <li>Konfirmasi import</li>
                      </ol>
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
