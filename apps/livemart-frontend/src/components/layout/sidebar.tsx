'use client';

import * as React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { 
  Home, 
  Package, 
  Warehouse, 
  ShoppingCart, 
  DollarSign, 
  BarChart3, 
  Database,
  RotateCcw,
  Settings,
  ChevronDown,
  ChevronRight,
  Sparkles,
} from 'lucide-react';
import { useSidebarStore } from '@/lib/stores/sidebar';

interface MenuItem {
  title: string;
  href?: string;
  icon: React.ReactNode;
  children?: MenuItem[];
  gradient?: string;
}

const menuItems: MenuItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: <Home className="w-5 h-5" />,
    gradient: 'from-blue-500 to-blue-600',
  },
  {
    title: 'Penerimaan',
    icon: <Package className="w-5 h-5" />,
    gradient: 'from-purple-500 to-purple-600',
    children: [
      { title: 'List Penerimaan', href: '/penerimaan', icon: null },
      { title: 'Tambah Penerimaan', href: '/penerimaan/create', icon: null },
    ],
  },
  {
    title: 'Warehouse',
    icon: <Warehouse className="w-5 h-5" />,
    gradient: 'from-green-500 to-green-600',
    children: [
      { title: 'Stock List', href: '/warehouse', icon: null },
      { title: 'Move Stock', href: '/warehouse/move', icon: null },
    ],
  },
  {
    title: 'Sales',
    icon: <ShoppingCart className="w-5 h-5" />,
    gradient: 'from-orange-500 to-orange-600',
    children: [
      { title: 'Pilih Jenis', href: '/sales/choose-type', icon: null },
      { title: 'Online Sales', href: '/sales/online', icon: null },
      { title: 'Offline Sales', href: '/sales/offline', icon: null },
      { title: 'List Orders', href: '/sales/list', icon: null },
    ],
  },
  {
    title: 'Finance',
    icon: <DollarSign className="w-5 h-5" />,
    gradient: 'from-emerald-500 to-emerald-600',
    children: [
      { title: 'Shopee Finance', href: '/finance/shopee', icon: null },
      { title: 'Shopee2 Finance', href: '/finance/shopee2', icon: null },
      { title: 'Tiktok Finance', href: '/finance/tiktok', icon: null },
      { title: 'Tiktok2 Finance', href: '/finance/tiktok2', icon: null },
      { title: 'Offline Finance', href: '/finance/offline', icon: null },
      { title: 'Unpaid Orders', href: '/finance/unpaid-orders', icon: null },
    ],
  },
  {
    title: 'Analytics',
    icon: <BarChart3 className="w-5 h-5" />,
    gradient: 'from-pink-500 to-pink-600',
    children: [
      { title: 'Sales Value', href: '/analytics/sales-value', icon: null },
      { title: 'Gross Profit', href: '/analytics/gross-profit', icon: null },
      { title: 'Reports', href: '/analytics', icon: null },
    ],
  },
  {
    title: 'Master Data',
    icon: <Database className="w-5 h-5" />,
    gradient: 'from-indigo-500 to-indigo-600',
    children: [
      { title: 'Products', href: '/master/products', icon: null },
      { title: 'Brands', href: '/master/brands', icon: null },
      { title: 'Customers', href: '/master/customers', icon: null },
      { title: 'Mapping', href: '/master/mapping', icon: null },
    ],
  },
  {
    title: 'Retur',
    icon: <RotateCcw className="w-5 h-5" />,
    gradient: 'from-red-500 to-red-600',
    children: [
      { title: 'Retur Pembelian', href: '/retur/pembelian', icon: null },
      { title: 'Retur Penjualan', href: '/retur/penjualan', icon: null },
      { title: 'Retur Offline', href: '/retur/offline', icon: null },
    ],
  },
  {
    title: 'Admin',
    icon: <Settings className="w-5 h-5" />,
    gradient: 'from-gray-600 to-gray-700',
    children: [
      { title: 'Users', href: '/admin/users', icon: null },
      { title: 'Roles', href: '/admin/roles', icon: null },
    ],
  },
];

export function Sidebar() {
  const pathname = usePathname();
  const { isOpen } = useSidebarStore();
  const [expandedItems, setExpandedItems] = React.useState<string[]>([]);

  const toggleItem = (title: string) => {
    setExpandedItems((prev) =>
      prev.includes(title)
        ? prev.filter((item) => item !== title)
        : [...prev, title]
    );
  };

  if (!isOpen) return null;

  return (
    <aside className="w-72 bg-white border-r border-gray-200 min-h-screen shadow-sm">
      {/* Logo Section */}
      <div className="p-6 border-b border-gray-100">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl gradient-primary flex items-center justify-center">
            <Sparkles className="w-5 h-5 text-white" />
          </div>
          <div>
            <h1 className="text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
              Liefmart
            </h1>
            <p className="text-xs text-gray-500">ERP System</p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="p-4 space-y-1 max-h-[calc(100vh-100px)] overflow-y-auto">
        {menuItems.map((item) => (
          <div key={item.title} className="mb-1">
            {item.children ? (
              <div>
                <button
                  onClick={() => toggleItem(item.title)}
                  className="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 group"
                >
                  <div className="flex items-center gap-3">
                    <div className={`w-8 h-8 rounded-lg bg-gradient-to-br ${item.gradient} flex items-center justify-center text-white shadow-sm`}>
                      {item.icon}
                    </div>
                    <span className="group-hover:text-gray-900">{item.title}</span>
                  </div>
                  {expandedItems.includes(item.title) ? (
                    <ChevronDown className="w-4 h-4 text-gray-400" />
                  ) : (
                    <ChevronRight className="w-4 h-4 text-gray-400" />
                  )}
                </button>
                {expandedItems.includes(item.title) && (
                  <div className="ml-4 mt-1 space-y-1 animate-fade-in">
                    {item.children.map((child) => (
                      <Link
                        key={child.title}
                        href={child.href || '#'}
                        className={`block px-4 py-2 text-sm rounded-lg transition-all duration-200 ${
                          pathname === child.href
                            ? 'bg-blue-50 text-blue-600 font-medium shadow-sm'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        }`}
                      >
                        {child.title}
                      </Link>
                    ))}
                  </div>
                )}
              </div>
            ) : (
              <Link
                href={item.href || '#'}
                className={`flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group ${
                  pathname === item.href
                    ? 'bg-blue-50 text-blue-600 shadow-sm'
                    : 'text-gray-700 hover:bg-gray-50'
                }`}
              >
                <div className={`w-8 h-8 rounded-lg bg-gradient-to-br ${item.gradient} flex items-center justify-center text-white shadow-sm`}>
                  {item.icon}
                </div>
                <span className="group-hover:text-gray-900">{item.title}</span>
              </Link>
            )}
          </div>
        ))}
      </nav>
    </aside>
  );
}
