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
  Package,
  ArrowUpRight,
  Sparkles,
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

function StatCard({ title, value, icon, trend, loading, gradient }: {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  trend?: number;
  loading?: boolean;
  gradient: string;
}) {
  return (
    <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm card-hover">
      <div className="flex items-start justify-between mb-4">
        <div className={`w-12 h-12 rounded-xl bg-gradient-to-br ${gradient} flex items-center justify-center shadow-lg`}>
          <div className="text-white">{icon}</div>
        </div>
        {trend !== undefined && (
          <div className={`flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${
            trend >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
          }`}>
            {trend >= 0 ? <TrendingUp className="w-3 h-3" /> : <TrendingDown className="w-3 h-3" />}
            {Math.abs(trend)}%
          </div>
        )}
      </div>
      
      {loading ? (
        <div className="space-y-2">
          <div className="h-4 bg-gray-200 rounded animate-pulse w-24"></div>
          <div className="h-8 bg-gray-200 rounded animate-pulse w-32"></div>
        </div>
      ) : (
        <>
          <p className="text-sm font-medium text-gray-500 mb-1">{title}</p>
          <div className="text-3xl font-bold text-gray-900">{value}</div>
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
          <div className="max-w-7xl mx-auto space-y-8">
            {/* Header */}
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
                  Dashboard
                  <Sparkles className="w-6 h-6 text-yellow-500" />
                </h1>
                <p className="text-gray-500 mt-1">Welcome back! Here's what's happening today.</p>
              </div>
              <button className="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all">
                Export Report
              </button>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <StatCard
                title="Total Sales"
                value={stats ? `Rp ${(stats.total_sales / 1000000).toFixed(1)}M` : 'Rp 0'}
                icon={<DollarSign className="w-6 h-6" />}
                trend={15.2}
                loading={isLoading}
                gradient="from-blue-500 to-blue-600"
              />
              <StatCard
                title="Total Orders"
                value={stats?.total_orders?.toLocaleString() || 0}
                icon={<ShoppingCart className="w-6 h-6" />}
                trend={8.5}
                loading={isLoading}
                gradient="from-purple-500 to-purple-600"
              />
              <StatCard
                title="Total Customers"
                value={stats?.total_customers || 0}
                icon={<Users className="w-6 h-6" />}
                trend={12.3}
                loading={isLoading}
                gradient="from-green-500 to-green-600"
              />
              <StatCard
                title="Total Products"
                value={stats?.total_products || 0}
                icon={<Package className="w-6 h-6" />}
                loading={isLoading}
                gradient="from-orange-500 to-orange-600"
              />
            </div>

            {/* Sales by Platform */}
            <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h2 className="text-xl font-bold text-gray-900">Sales by Platform</h2>
                  <p className="text-sm text-gray-500 mt-1">Performance across all platforms</p>
                </div>
                <button className="text-sm text-blue-600 font-medium hover:text-blue-700 flex items-center gap-1">
                  View All
                  <ArrowUpRight className="w-4 h-4" />
                </button>
              </div>

              {isLoading ? (
                <div className="space-y-4">
                  {[1, 2, 3, 4].map((i) => (
                    <div key={i} className="h-16 bg-gray-100 rounded-lg animate-pulse"></div>
                  ))}
                </div>
              ) : (
                <div className="space-y-4">
                  {stats?.sales_by_platform?.map((platform: any, index: number) => {
                    const colors = [
                      'from-orange-500 to-orange-600',
                      'from-orange-600 to-orange-700',
                      'from-gray-800 to-gray-900',
                      'from-gray-600 to-gray-700',
                    ];
                    const percentage = (platform.total_sales / stats.total_sales) * 100;
                    
                    return (
                      <div key={platform.platform} className="group">
                        <div className="flex items-center justify-between mb-2">
                          <div className="flex items-center gap-3">
                            <div className={`w-10 h-10 rounded-lg bg-gradient-to-br ${colors[index % colors.length]} flex items-center justify-center text-white font-bold text-sm shadow-sm`}>
                              {platform.platform.charAt(0).toUpperCase()}
                            </div>
                            <div>
                              <p className="text-sm font-semibold text-gray-900 capitalize">{platform.platform}</p>
                              <p className="text-xs text-gray-500">{platform.total_orders} orders</p>
                            </div>
                          </div>
                          <div className="text-right">
                            <p className="text-sm font-bold text-gray-900">Rp {(platform.total_sales / 1000000).toFixed(1)}M</p>
                            <p className={`text-xs font-semibold ${platform.growth_percentage >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                              {platform.growth_percentage >= 0 ? '+' : ''}{platform.growth_percentage}%
                            </p>
                          </div>
                        </div>
                        <div className="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                          <div
                            className={`h-full rounded-full bg-gradient-to-r ${colors[index % colors.length]} transition-all duration-500`}
                            style={{ width: `${percentage}%` }}
                          ></div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>

            {/* Recent Activity & Alerts */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Recent Transactions</h2>
                <div className="space-y-3">
                  <div className="flex items-center justify-center py-8 text-gray-400">
                    <p className="text-sm">No recent transactions</p>
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Low Stock Alerts</h2>
                <div className="space-y-3">
                  <div className="flex items-center justify-center py-8 text-gray-400">
                    <p className="text-sm">No low stock alerts</p>
                  </div>
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
