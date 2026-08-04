'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { 
  TrendingUp, 
  TrendingDown, 
  ShoppingCart, 
  DollarSign, 
  Users, 
  Package 
} from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboardApi } from '@/lib/api';
import { formatCurrency } from '@/lib/utils';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

interface StatCardProps {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  trend?: number;
  loading?: boolean;
}

function StatCard({ title, value, icon, trend, loading }: StatCardProps) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">{title}</CardTitle>
        <div className="h-8 w-8 text-muted-foreground">{icon}</div>
      </CardHeader>
      <CardContent>
        {loading ? (
          <div className="h-8 bg-gray-200 animate-pulse rounded"></div>
        ) : (
          <>
            <div className="text-2xl font-bold">{value}</div>
            {trend !== undefined && (
              <p className="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                {trend >= 0 ? (
                  <>
                    <TrendingUp className="h-4 w-4 text-green-500" />
                    <span className="text-green-500">+{trend}%</span>
                  </>
                ) : (
                  <>
                    <TrendingDown className="h-4 w-4 text-red-500" />
                    <span className="text-red-500">{trend}%</span>
                  </>
                )}
                <span>dari bulan lalu</span>
              </p>
            )}
          </>
        )}
      </CardContent>
    </Card>
  );
}

function DashboardContent() {
  const router = useRouter();

  const { data: stats, isLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: async () => {
      const response = await dashboardApi.getStats();
      return response.data;
    },
  });

  const { data: recentTransactions } = useQuery({
    queryKey: ['recent-transactions'],
    queryFn: async () => {
      const response = await dashboardApi.getRecentTransactions(10);
      return response.data;
    },
  });

  const { data: lowStockProducts } = useQuery({
    queryKey: ['low-stock-alerts'],
    queryFn: async () => {
      const response = await dashboardApi.getLowStockAlerts(10);
      return response.data;
    },
  });

  return (
    <div className="h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 flex overflow-hidden">
        <Sidebar />
        <main className="flex-1 overflow-y-auto bg-gray-50">
          <div className="container mx-auto p-6">
            <div className="space-y-6">
              <div>
                <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
                <p className="text-muted-foreground">
                  Selamat datang di Liefmart ERP System
                </p>
              </div>

              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatCard
                  title="Total Sales"
                  value={stats ? formatCurrency(stats.total_sales) : 'Rp 0'}
                  icon={<DollarSign />}
                  trend={15.2}
                  loading={isLoading}
                />
                <StatCard
                  title="Total Orders"
                  value={stats?.total_orders || 0}
                  icon={<ShoppingCart />}
                  trend={8.5}
                  loading={isLoading}
                />
                <StatCard
                  title="Total Customers"
                  value={stats?.total_customers || 0}
                  icon={<Users />}
                  trend={12.3}
                  loading={isLoading}
                />
                <StatCard
                  title="Total Products"
                  value={stats?.total_products || 0}
                  icon={<Package />}
                  loading={isLoading}
                />
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Sales by Platform</CardTitle>
                </CardHeader>
                <CardContent>
                  {isLoading ? (
                    <div className="space-y-3">
                      {[1, 2, 3, 4].map((i) => (
                        <div key={i} className="h-12 bg-gray-200 animate-pulse rounded"></div>
                      ))}
                    </div>
                  ) : (
                    <div className="space-y-4">
                      {stats?.sales_by_platform?.map((platform: any) => (
                        <div key={platform.platform} className="flex items-center justify-between">
                          <div className="flex-1">
                            <div className="flex items-center justify-between mb-1">
                              <span className="text-sm font-medium capitalize">
                                {platform.platform}
                              </span>
                              <span className="text-sm text-muted-foreground">
                                {platform.total_orders} orders
                              </span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2">
                              <div
                                className="bg-blue-600 h-2 rounded-full"
                                style={{
                                  width: `${(platform.total_sales / stats.total_sales) * 100}%`,
                                }}
                              ></div>
                            </div>
                          </div>
                          <div className="ml-4 text-right">
                            <div className="text-sm font-semibold">
                              {formatCurrency(platform.total_sales)}
                            </div>
                            {platform.growth_percentage !== undefined && (
                              <div className={`text-xs ${platform.growth_percentage >= 0 ? 'text-green-500' : 'text-red-500'}`}>
                                {platform.growth_percentage >= 0 ? '+' : ''}
                                {platform.growth_percentage}%
                              </div>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>

              <div className="grid gap-4 md:grid-cols-2">
                <Card>
                  <CardHeader>
                    <CardTitle>Recent Transactions</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {recentTransactions?.map((transaction: any) => (
                        <div
                          key={transaction.id}
                          className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                        >
                          <div className="flex-1">
                            <p className="text-sm font-medium">{transaction.order_number}</p>
                            <p className="text-xs text-muted-foreground">
                              {transaction.customer_name} • {transaction.platform}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="text-sm font-semibold">
                              {formatCurrency(transaction.total_amount)}
                            </p>
                            <p className={`text-xs ${
                              transaction.status === 'completed' ? 'text-green-500' : 
                              transaction.status === 'pending' ? 'text-yellow-500' : 
                              'text-gray-500'
                            }`}>
                              {transaction.status}
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle>Low Stock Alerts</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {lowStockProducts?.map((product: any) => (
                        <div
                          key={product.id}
                          className="flex items-center justify-between p-3 bg-red-50 rounded-lg"
                        >
                          <div className="flex-1">
                            <p className="text-sm font-medium">{product.name}</p>
                            <p className="text-xs text-muted-foreground">
                              SKU: {product.sku || '-'}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="text-sm font-semibold text-red-600">
                              {product.stock} pcs
                            </p>
                            <p className="text-xs text-muted-foreground">tersisa</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}

export default function DashboardPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <DashboardContent />
    </QueryClientProvider>
  );
}
