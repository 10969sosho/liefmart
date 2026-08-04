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
import { dashboardApi } from '@/lib/api';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

function StatCard({ title, value, icon, trend, loading }: {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  trend?: number;
  loading?: boolean;
}) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-sm font-medium text-gray-500">{title}</h3>
        <div className="text-gray-400">{icon}</div>
      </div>
      {loading ? (
        <div className="h-8 bg-gray-200 animate-pulse rounded"></div>
      ) : (
        <>
          <div className="text-3xl font-bold text-gray-900">{value}</div>
          {trend !== undefined && (
            <div className="flex items-center gap-1 mt-2">
              {trend >= 0 ? (
                <TrendingUp className="h-4 w-4 text-green-500" />
              ) : (
                <TrendingDown className="h-4 w-4 text-red-500" />
              )}
              <span className={`text-sm font-medium ${trend >= 0 ? 'text-green-500' : 'text-red-500'}`}>
                {trend >= 0 ? '+' : ''}{trend}%
              </span>
              <span className="text-sm text-gray-500">dari bulan lalu</span>
            </div>
          )}
        </>
      )}
    </div>
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

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-8">
          <div className="max-w-7xl mx-auto">
            <div className="mb-8">
              <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
              <p className="text-gray-500 mt-1">Selamat datang di Liefmart ERP System</p>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <StatCard
                title="Total Sales"
                value={stats ? `Rp ${(stats.total_sales / 1000000).toFixed(1)}M` : 'Rp 0'}
                icon={<DollarSign className="w-5 h-5" />}
                trend={15.2}
                loading={isLoading}
              />
              <StatCard
                title="Total Orders"
                value={stats?.total_orders || 0}
                icon={<ShoppingCart className="w-5 h-5" />}
                trend={8.5}
                loading={isLoading}
              />
              <StatCard
                title="Total Customers"
                value={stats?.total_customers || 0}
                icon={<Users className="w-5 h-5" />}
                trend={12.3}
                loading={isLoading}
              />
              <StatCard
                title="Total Products"
                value={stats?.total_products || 0}
                icon={<Package className="w-5 h-5" />}
                loading={isLoading}
              />
            </div>

            {/* Sales by Platform */}
            <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Sales by Platform</h2>
              {isLoading ? (
                <div className="space-y-3">
                  {[1, 2, 3, 4].map((i) => (
                    <div key={i} className="h-12 bg-gray-200 animate-pulse rounded"></div>
                  ))}
                </div>
              ) : (
                <div className="space-y-4">
                  {stats?.sales_by_platform?.map((platform: any) => (
                    <div key={platform.platform} className="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                      <div className="flex-1">
                        <div className="flex items-center justify-between mb-2">
                          <span className="text-sm font-medium text-gray-900 capitalize">
                            {platform.platform}
                          </span>
                          <span className="text-sm text-gray-500">
                            {platform.total_orders} orders
                          </span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-2">
                          <div
                            className="bg-blue-600 h-2 rounded-full transition-all"
                            style={{
                              width: `${(platform.total_sales / stats.total_sales) * 100}%`,
                            }}
                          ></div>
                        </div>
                      </div>
                      <div className="ml-6 text-right min-w-[120px]">
                        <div className="text-sm font-semibold text-gray-900">
                          Rp {(platform.total_sales / 1000000).toFixed(1)}M
                        </div>
                        {platform.growth_percentage !== undefined && (
                          <div className={`text-xs font-medium ${platform.growth_percentage >= 0 ? 'text-green-500' : 'text-red-500'}`}>
                            {platform.growth_percentage >= 0 ? '+' : ''}
                            {platform.growth_percentage}%
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Recent Transactions & Low Stock */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-gray-900 mb-4">Recent Transactions</h2>
                <div className="space-y-3">
                  <p className="text-sm text-gray-500">No recent transactions</p>
                </div>
              </div>

              <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-gray-900 mb-4">Low Stock Alerts</h2>
                <div className="space-y-3">
                  <p className="text-sm text-gray-500">No low stock alerts</p>
                </div>
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
