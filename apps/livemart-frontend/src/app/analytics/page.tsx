'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { BarChart3, TrendingUp, DollarSign, FileDown, PieChart, Calendar } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function AnalyticsPage() {
  const router = useRouter();

  const reports = [
    { title: 'Sales Value Report', desc: 'Laporan nilai penjualan per periode', icon: DollarSign, color: 'bg-blue-600', path: '/analytics/sales-value' },
    { title: 'Sales Volume Report', desc: 'Laporan volume penjualan (qty)', icon: BarChart3, color: 'bg-green-600', path: '/analytics/sales-volume' },
    { title: 'Gross Profit Report', desc: 'Laporan laba kotor per produk', icon: TrendingUp, color: 'bg-purple-600', path: '/analytics/gross-profit' },
    { title: 'Monthly Summary', desc: 'Ringkasan penjualan bulanan', icon: Calendar, color: 'bg-orange-600', path: '/analytics/monthly-summary' },
    { title: 'Sales by Platform', desc: 'Perbandingan penjualan per platform', icon: PieChart, color: 'bg-red-600', path: '/analytics/sales-by-platform' },
    { title: 'Sales Detail Report', desc: 'Detail transaksi penjualan', icon: FileDown, color: 'bg-indigo-600', path: '/analytics/sales-detail' },
    { title: 'Offline Analytics', desc: 'Analitik penjualan offline', icon: BarChart3, color: 'bg-teal-600', path: '/analytics/offline' },
    { title: 'Export Queue', desc: 'Antrian export laporan', icon: FileDown, color: 'bg-gray-700', path: '/analytics/exports' },
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
                <h1 className="text-3xl font-bold tracking-tight">Analytics & Reports</h1>
                <p className="text-muted-foreground">
                  Laporan dan analitik penjualan, keuangan, dan operasional
                </p>
              </div>

              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {reports.map((report) => {
                  const Icon = report.icon;
                  return (
                    <Card
                      key={report.path}
                      className="cursor-pointer hover:shadow-lg transition-shadow"
                      onClick={() => router.push(report.path)}
                    >
                      <CardHeader className="pb-2">
                        <div className={`${report.color} w-10 h-10 rounded-lg flex items-center justify-center mb-2`}>
                          <Icon className="w-5 h-5 text-white" />
                        </div>
                        <CardTitle className="text-sm">{report.title}</CardTitle>
                        <CardDescription className="text-xs">{report.desc}</CardDescription>
                      </CardHeader>
                    </Card>
                  );
                })}
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}
