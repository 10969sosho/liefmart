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
  Users, 
  Settings,
  ChevronDown,
  ChevronRight,
  RotateCcw,
  Database,
} from 'lucide-react';
import { useSidebarStore } from '@/lib/stores/sidebar';

interface MenuItem {
  title: string;
  href?: string;
  icon: React.ReactNode;
  children?: MenuItem[];
}

const menuItems: MenuItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: <Home className="w-5 h-5" />,
  },
  {
    title: 'Penerimaan',
    icon: <Package className="w-5 h-5" />,
    children: [
      { title: 'List Penerimaan', href: '/penerimaan', icon: null },
      { title: 'Tambah Penerimaan', href: '/penerimaan/create', icon: null },
    ],
  },
  {
    title: 'Warehouse',
    icon: <Warehouse className="w-5 h-5" />,
    children: [
      { title: 'Stock List', href: '/warehouse/stock', icon: null },
      { title: 'Move to Warehouse', href: '/warehouse/move', icon: null },
      { title: 'Analytics', href: '/warehouse/analytics', icon: null },
    ],
  },
  {
    title: 'Sales',
    icon: <ShoppingCart className="w-5 h-5" />,
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
    children: [
      { title: 'Sales Value Report', href: '/analytics/sales-value', icon: null },
      { title: 'Sales Volume Report', href: '/analytics/sales-volume', icon: null },
      { title: 'Gross Profit Report', href: '/analytics/gross-profit', icon: null },
      { title: 'Monthly Summary', href: '/analytics/monthly-summary', icon: null },
      { title: 'Sales by Platform', href: '/analytics/sales-by-platform', icon: null },
      { title: 'Sales Detail Report', href: '/analytics/sales-detail', icon: null },
      { title: 'Offline Analytics', href: '/analytics/offline', icon: null },
      { title: 'Export Queue', href: '/analytics/exports', icon: null },
    ],
  },
  {
    title: 'Master Data',
    icon: <Database className="w-5 h-5" />,
    children: [
      { title: 'Products', href: '/master/products', icon: null },
      { title: 'Brands', href: '/master/brands', icon: null },
      { title: 'Customers', href: '/master/customers', icon: null },
      { title: 'Mapping Barang', href: '/master/mapping', icon: null },
    ],
  },
  {
    title: 'Retur',
    icon: <RotateCcw className="w-5 h-5" />,
    children: [
      { title: 'Retur Pembelian', href: '/retur/pembelian', icon: null },
      { title: 'Retur Penjualan', href: '/retur/penjualan', icon: null },
      { title: 'Retur Offline', href: '/retur/offline', icon: null },
    ],
  },
  {
    title: 'Admin',
    icon: <Settings className="w-5 h-5" />,
    children: [
      { title: 'Users', href: '/admin/users', icon: null },
      { title: 'Roles', href: '/admin/roles', icon: null },
      { title: 'Permissions', href: '/admin/permissions', icon: null },
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
    <aside className="w-64 bg-white border-r border-gray-200 min-h-screen">
      <div className="p-6 border-b border-gray-200">
        <h1 className="text-xl font-bold text-gray-900">Liefmart</h1>
        <p className="text-xs text-gray-500 mt-1">ERP System</p>
      </div>

      <nav className="p-4 space-y-1">
        {menuItems.map((item) => (
          <div key={item.title} className="mb-1">
            {item.children ? (
              <div>
                <button
                  onClick={() => toggleItem(item.title)}
                  className="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    {item.icon}
                    <span>{item.title}</span>
                  </div>
                  {expandedItems.includes(item.title) ? (
                    <ChevronDown className="w-4 h-4" />
                  ) : (
                    <ChevronRight className="w-4 h-4" />
                  )}
                </button>
                {expandedItems.includes(item.title) && (
                  <div className="ml-4 mt-1 space-y-1">
                    {item.children.map((child) => (
                      <Link
                        key={child.title}
                        href={child.href || '#'}
                        className={`block px-3 py-2 text-sm rounded-md transition-colors ${
                          pathname === child.href
                            ? 'bg-blue-50 text-blue-600 font-medium'
                            : 'text-gray-600 hover:bg-gray-100'
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
                className={`flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                  pathname === item.href
                    ? 'bg-blue-50 text-blue-600'
                    : 'text-gray-700 hover:bg-gray-100'
                }`}
              >
                {item.icon}
                <span>{item.title}</span>
              </Link>
            )}
          </div>
        ))}
      </nav>
    </aside>
  );
}
