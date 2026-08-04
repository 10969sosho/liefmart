'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Download } from 'lucide-react';
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

function GrossProfitContent() {
  const router = useRouter();
  const [startDate, setStartDate] = React.useState('2026-01-01');
  const [endDate, setEndDate] = React.useState('2026-08-04');
  const [platform, setPlatform] = React.useState('');

  const reportData = [
    { product_name: 'Tumbler SS 500ml', qty_sold: 150, revenue: 7500000, cost: 4500000, gross_profit: 3000000, margin: 40 },
    { product_name: 'Botol Minum 1L', qty_sold: 120, revenue: 6000000, cost: 3600000, gross_profit: 2400000, margin: 40 },
    { product_name: 'Wadah Makanan Set', qty_sold: 80, revenue: 4000000, cost: 2800000, gross_profit: 1200000, margin: 30 },
    { product_name: 'Lunch Box 3 Tier', qty_sold: 60, revenue: 3000000, cost: 1800000, gross_profit: 1200000, margin: 40 },
  ];

  const totalRevenue = reportData.reduce((s, r) => s + r.revenue, 0);
  const totalCost = reportData.reduce((s, r) => s + r.cost, 0);
  const totalProfit = reportData.reduce((s, r) => s + r.gross_profit, 0);

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
                  <h1 className="text-3xl font-bold tracking-tight">Gross Profit Report</h1>
                  <p className="text-muted-foreground">Laporan laba kotor per produk</p>
                </div>
                <Button variant="outline" onClick={() => router.push('/analytics')}>Kembali</Button>
              </div>

              <Card>
                <CardHeader><CardTitle>Filter</CardTitle></CardHeader>
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

              <div className="grid gap-4 md:grid-cols-4">
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Total Revenue</CardTitle></CardHeader>
                  <CardContent><div className="text-2xl font-bold">{formatCurrency(totalRevenue)}</div></CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Total COGS</CardTitle></CardHeader>
                  <CardContent><div className="text-2xl font-bold text-red-600">{formatCurrency(totalCost)}</div></CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Gross Profit</CardTitle></CardHeader>
                  <CardContent><div className="text-2xl font-bold text-green-600">{formatCurrency(totalProfit)}</div></CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Margin %</CardTitle></CardHeader>
                  <CardContent><div className="text-2xl font-bold text-blue-600">{totalRevenue > 0 ? ((totalProfit / totalRevenue) * 100).toFixed(1) : 0}%</div></CardContent>
                </Card>
              </div>

              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle>Detail Gross Profit per Produk</CardTitle>
                    <Button variant="outline" size="sm"><Download className="w-4 h-4 mr-2" />Export Excel</Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Produk</TableHead>
                        <TableHead className="text-right">Qty Sold</TableHead>
                        <TableHead className="text-right">Revenue</TableHead>
                        <TableHead className="text-right">COGS</TableHead>
                        <TableHead className="text-right">Gross Profit</TableHead>
                        <TableHead className="text-right">Margin %</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {reportData.map((row, i) => (
                        <TableRow key={i}>
                          <TableCell className="font-medium">{row.product_name}</TableCell>
                          <TableCell className="text-right">{row.qty_sold}</TableCell>
                          <TableCell className="text-right">{formatCurrency(row.revenue)}</TableCell>
                          <TableCell className="text-right text-red-600">{formatCurrency(row.cost)}</TableCell>
                          <TableCell className="text-right font-semibold text-green-600">{formatCurrency(row.gross_profit)}</TableCell>
                          <TableCell className="text-right">
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${row.margin >= 35 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                              {row.margin}%
                            </span>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                    <TableFooter>
                      <TableRow>
                        <TableCell className="font-bold">TOTAL</TableCell>
                        <TableCell className="text-right font-bold">{reportData.reduce((s, r) => s + r.qty_sold, 0)}</TableCell>
                        <TableCell className="text-right font-bold">{formatCurrency(totalRevenue)}</TableCell>
                        <TableCell className="text-right font-bold text-red-600">{formatCurrency(totalCost)}</TableCell>
                        <TableCell className="text-right font-bold text-green-600">{formatCurrency(totalProfit)}</TableCell>
                        <TableCell className="text-right font-bold text-blue-600">{totalRevenue > 0 ? ((totalProfit / totalRevenue) * 100).toFixed(1) : 0}%</TableCell>
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

export default function GrossProfitPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <GrossProfitContent />
    </QueryClientProvider>
  );
}
