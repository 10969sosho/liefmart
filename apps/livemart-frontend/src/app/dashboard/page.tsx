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
  Activity,
  AlertCircle,
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

function StatCard({ title, value, icon, trend, loading, color }: {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  trend?: number;
  loading?: boolean;
  color: string;
}) {
  const colorClasses: Record<string, string> = {
    blue: 'from-blue-500 to-blue-600',
    purple: 'from-purple-500 to-purple-600',
    green: 'from-green-500 to-green-600',
    orange: 'from-orange-500 to-orange-600',
  };

  return (
    <div className="bg-white rounded-2xl border border-gray-200/60 p-6 hover:shadow-lg transition-all duration-300 group">
      <div className="flex items-start justify-between mb-4">
        <div className={`w-14 h-14 rounded-xl bg-gradient-to-br ${colorClasses[color]} flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300`}>
          <div className="text-white">{icon}</div>
        </div>
        {trend !== undefined && (
          <div className={`flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold ${
            trend >= 0 ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' : 'bg-red-50 text-red-700 ring-1 ring-red-600/20'
          }`}>
            {trend >= 0 ? <TrendingUp className="w-3.5 h-3.5" /> : <TrendingDown className="w-3.5 h-3.5" />}
            {Math.abs(trend)}%
          </div>
        )}
      </div>
      
      {loading ? (
        <div className="space-y-3">
          <div className="h-4 bg-gray-200 rounded-lg skeleton-shimmer w-24"></div>
          <div className="h-9 bg-gray-200 rounded-lg skeleton-shimmer w-36"></div>
        </div>
      ) : (
        <>
          <p className="text-sm font-medium text-gray-500 mb-2">{title}</p>
          <div className="text-3xl font-bold text-gray-900 tracking-tight">{value}</div>
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
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100/50">
      <Navbar />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-8">
          <div className="max-w-7xl mx-auto space-y-8">
            {/* Header */}
            <div className="flex items-center justify-between">
              <div>
                <div className="flex items-center gap-3">
                  <h1 className="text-4xl font-bold text-gray-900">
                    Dashboard
                  </h1>
                  <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center shadow-lg animate-pulse">
                    <Sparkles className="w-5 h-5 text-white" />
                  </div>
                </div>
                <p className="text-gray-500 mt-2 text-sm">Welcome back! Here's what's happening today.</p>
              </div>
              <button className="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all duration-200 flex items-center gap-2">
                <ArrowUpRight className="w-4 h-4" />
                Export Report
              </button>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <StatCard
                title="Total Sales"
                value={stats ? `Rp ${(stats.total_sales / 1000000).toFixed(1)}M` : 'Rp 0'}
                icon={<DollarSign className="w-7 h-7" />}
                trend={15.2}
                loading={isLoading}
                color="blue"
              />
              <StatCard
                title="Total Orders"
                value={stats?.total_orders?.toLocaleString() || 0}
                icon={<ShoppingCart className="w-7 h-7" />}
                trend={8.5}
                loading={isLoading}
                color="purple"
              />
              <StatCard
                title="Total Customers"
                value={stats?.total_customers || 0}
                icon={<Users className="w-7 h-7" />}
                trend={12.3}
                loading={isLoading}
                color="green"
              />
              <StatCard
                title="Total Products"
                value={stats?.total_products || 0}
                icon={<Package className="w-7 h-7" />}
                loading={isLoading}
                color="orange"
              />
            </div>

            {/* Sales by Platform */}
            <div className="bg-white rounded-2xl border border-gray-200/60 p-8 hover:shadow-lg transition-all duration-300">
              <div className="flex items-center justify-between mb-8">
                <div>
                  <div className="flex items-center gap-3 mb-2">
                    <Activity className="w-6 h-6 text-gray-700" />
                    <h2 className="text-2xl font-bold text-gray-900">Sales by Platform</h2>
                  </div>
                  <p className="text-sm text-gray-500">Performance across all platforms</p>
                </div>
                <button className="text-sm text-blue-600 font-semibold hover:text-blue-700 flex items-center gap-1.5 px-4 py-2 rounded-xl hover:bg-blue-50 transition-all duration-200 group">
                  View All
                  <ArrowUpRight className="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform duration-200" />
                </button>
              </div>

              {isLoading ? (
                <div className="space-y-4">
                  {[1, 2, 3, 4].map((i) => (
                    <div key={i} className="h-20 bg-gray-100 rounded-xl skeleton-shimmer"></div>
                  ))}
                </div>
              ) : (
                <div className="space-y-4">
                  {stats?.sales_by_platform?.map((platform: any, index: number) => {
                    const colors = [
                      { from: 'from-orange-500', to: 'to-orange-600', bg: 'bg-orange-500' },
                      { from: 'from-orange-600', to: 'to-orange-700', bg: 'bg-orange-600' },
                      { from: 'from-gray-700', to: 'to-gray-800', bg: 'bg-gray-700' },
                      { from: 'from-gray-600', to: 'to-gray-700', bg: 'bg-gray-600' },
                    ];
                    const color = colors[index % colors.length];
                    const percentage = (platform.total_sales / stats.total_sales) * 100;
                    
                    return (
                      <div key={platform.platform} className="group hover:bg-gray-50/50 p-4 rounded-xl transition-all duration-200">
                        <div className="flex items-center justify-between mb-3">
                          <div className="flex items-center gap-4">
                            <div className={`w-12 h-12 rounded-xl bg-gradient-to-br ${color.from} ${color.to} flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:scale-110 transition-transform duration-200`}>
                              {platform.platform.charAt(0).toUpperCase()}
                            </div>
                            <div>
                              <p className="text-base font-bold text-gray-900 capitalize">{platform.platform}</p>
                              <p className="text-xs text-gray-500 font-medium">{platform.total_orders} orders</p>
                            </div>
                          </div>
                          <div className="text-right">
                            <p className="text-base font-bold text-gray-900">Rp {(platform.total_sales / 1000000).toFixed(1)}M</p>
                            <p className={`text-xs font-bold flex items-center justify-end gap-1 ${platform.growth_percentage >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                              {platform.growth_percentage >= 0 ? <TrendingUp className="w-3 h-3" /> : <TrendingDown className="w-3 h-3" />}
                              {platform.growth_percentage >= 0 ? '+' : ''}{platform.growth_percentage}%
                            </p>
                          </div>
                        </div>
                        <div className="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                          <div
                            className={`h-full rounded-full ${color.bg} transition-all duration-700 ease-out`}
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
              {/* Recent Transactions */}
              <div className="bg-white rounded-2xl border border-gray-200/60 p-8 hover:shadow-lg transition-all duration-300">
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                    <Activity className="w-5 h-5 text-blue-600" />
                  </div>
                  <h2 className="text-xl font-bold text-gray-900">Recent Transactions</h2>
                </div>
                <div className="flex flex-col items-center justify-center py-12">
                  <div className="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                    <ShoppingCart className="w-8 h-8 text-gray-400" />
                  </div>
                  <p className="text-sm text-gray-500 font-medium">No recent transactions</p>
                  <p className="text-xs text-gray-400 mt-1">Transactions will appear here</p>
                </div>
              </div>

              {/* Low Stock Alerts */}
              <div className="bg-white rounded-2xl border border-gray-200/60 p-8 hover:shadow-lg transition-all duration-300">
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-10 h-10 rounded-xl bg-orange-500/10 flex items-center justify-center">
                    <AlertCircle className="w-5 h-5 text-orange-600" />
                  </div>
                  <h2 className="text-xl font-bold text-gray-900">Low Stock Alerts</h2>
                </div>
                <div className="flex flex-col items-center justify-center py-12">
                  <div className="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                    <Package className="w-8 h-8 text-gray-400" />
                  </div>
                  <p className="text-sm text-gray-500 font-medium">No low stock alerts</p>
                  <p className="text-xs text-gray-400 mt-1">All products are well stocked</p>
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
