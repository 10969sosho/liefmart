'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BarChart3, Download, Calendar, Filter } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableFooter,
} from '@/components/ui/table';
import { formatCurrency, formatDate } from '@/lib/utils';

const queryClient = new QueryClient({
  defaultOptions: { queries: { refetchOnWindowFocus: false, retry: 1 } },
});

function SalesValueContent() {
  const router = useRouter();
  const [startDate, setStartDate] = React.useState('2026-01-01');
  const [endDate, setEndDate] = React.useState('2026-08-04');
  const [platform, setPlatform] = React.useState('');

  const reportData = [
    { date: '2026-08-04', platform: 'shopee', order_count: 15, total_sales: 2500000, avg_order: 166667 },
    { date: '2026-08-04', platform: 'tiktok', order_count: 12, total_sales: 1800000, avg_order: 150000 },
    { date: '2026-08-03', platform: 'shopee', order_count: 18, total_sales: 3200000, avg_order: 177778 },
    { date: '2026-08-03', platform: 'tiktok', order_count: 10, total_sales: 1500000, avg_order: 150000 },
    { date: '2026-08-02', platform: 'shopee', order_count: 20, total_sales: 3800000, avg_order: 190000 },
    { date: '2026-08-02', platform: 'offline', order_count: 5, total_sales: 750000, avg_order: 150000 },
  ];

  const filtered = reportData.filter(r =>
    (!platform || r.platform === platform) &&
    r.date >= startDate && r.date <= endDate
  );

  const totalSales = filtered.reduce((s, r) => s + r.total_sales, 0);
  const totalOrders = filtered.reduce((s, r) => s + r.order_count, 0);

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
                  <h1 className="text-3xl font-bold tracking-tight">Sales Value Report</h1>
                  <p className="text-muted-foreground">Laporan nilai penjualan per periode</p>
                </div>
                <Button variant="outline" onClick={() => router.push('/analytics')}>Kembali</Button>
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Filter</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid gap-4 md:grid-cols-3">
                    <div className="space-y-2">
                      <Label>Tanggal Mulai</Label>
                      <Input type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} />
                    </div>
                    <div className="space-y-2">
                      <Label>Tanggal Akhir</Label>
                      <Input type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} />
                    </div>
                    <div className="space-y-2">
                      <Label>Platform</Label>
                      <select value={platform} onChange={(e) => setPlatform(e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                        <option value="">Semua Platform</option>
                        <option value="shopee">Shopee Lamourad</option>
                        <option value="shopee2">Shopee Liefmarket</option>
                        <option value="tiktok">Tiktok Lamourad</option>
                        <option value="tiktok2">Tiktok Liefmarket</option>
                        <option value="offline">Offline</option>
                      </select>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <div className="grid gap-4 md:grid-cols-3">
                <Card>
                  <CardHeader className="pb-2">
                    <CardTitle className="text-sm">Total Sales</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">{formatCurrency(totalSales)}</div>
                  </CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-2">
                    <CardTitle className="text-sm">Total Orders</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">{totalOrders}</div>
                  </CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-2">
                    <CardTitle className="text-sm">Avg Order Value</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">{formatCurrency(totalOrders > 0 ? totalSales / totalOrders : 0)}</div>
                  </CardContent>
                </Card>
              </div>

              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle>Detail Sales Value</CardTitle>
                    <Button variant="outline" size="sm">
                      <Download className="w-4 h-4 mr-2" />
                      Export Excel
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Tanggal</TableHead>
                        <TableHead>Platform</TableHead>
                        <TableHead className="text-right">Jumlah Order</TableHead>
                        <TableHead className="text-right">Total Sales</TableHead>
                        <TableHead className="text-right">Avg Order Value</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {filtered.map((row, i) => (
                        <TableRow key={i}>
                          <TableCell>{formatDate(row.date)}</TableCell>
                          <TableCell><span className="capitalize inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{row.platform}</span></TableCell>
                          <TableCell className="text-right">{row.order_count}</TableCell>
                          <TableCell className="text-right font-semibold">{formatCurrency(row.total_sales)}</TableCell>
                          <TableCell className="text-right">{formatCurrency(row.avg_order)}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                    <TableFooter>
                      <TableRow>
                        <TableCell colSpan={2} className="font-bold">TOTAL</TableCell>
                        <TableCell className="text-right font-bold">{totalOrders}</TableCell>
                        <TableCell className="text-right font-bold">{formatCurrency(totalSales)}</TableCell>
                        <TableCell className="text-right font-bold">{formatCurrency(totalOrders > 0 ? totalSales / totalOrders : 0)}</TableCell>
                      </TableRow>
                    </TableFooter>
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

export default function SalesValuePage() {
  return (
    <QueryClientProvider client={queryClient}>
      <SalesValueContent />
    </QueryClientProvider>
  );
}
