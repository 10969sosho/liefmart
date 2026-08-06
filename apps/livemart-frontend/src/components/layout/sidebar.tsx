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
  ChevronsLeft,
  ChevronsRight,
} from 'lucide-react';
import { useSidebarStore } from '@/lib/stores/sidebar';

interface MenuItem {
  title: string;
  href?: string;
  icon: React.ReactNode;
  children?: MenuItem[];
  color: string;
}

const menuItems: MenuItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: <Home className="w-5 h-5" />,
    color: 'blue',
  },
  {
    title: 'Penerimaan',
    icon: <Package className="w-5 h-5" />,
    color: 'purple',
    children: [
      { title: 'List Penerimaan', href: '/penerimaan', icon: null, color: '' },
      { title: 'Tambah Penerimaan', href: '/penerimaan/create', icon: null, color: '' },
    ],
  },
  {
    title: 'Warehouse',
    icon: <Warehouse className="w-5 h-5" />,
    color: 'green',
    children: [
      { title: 'Stock List', href: '/warehouse', icon: null, color: '' },
      { title: 'Move Stock', href: '/warehouse/move', icon: null, color: '' },
    ],
  },
  {
    title: 'Sales',
    icon: <ShoppingCart className="w-5 h-5" />,
    color: 'orange',
    children: [
      { title: 'Pilih Jenis', href: '/sales/choose-type', icon: null, color: '' },
      { title: 'Online Sales', href: '/sales/online', icon: null, color: '' },
      { title: 'Offline Sales', href: '/sales/offline', icon: null, color: '' },
      { title: 'List Orders', href: '/sales/list', icon: null, color: '' },
    ],
  },
  {
    title: 'Finance',
    icon: <DollarSign className="w-5 h-5" />,
    color: 'emerald',
    children: [
      { title: 'Shopee Finance', href: '/finance/shopee', icon: null, color: '' },
      { title: 'Shopee2 Finance', href: '/finance/shopee2', icon: null, color: '' },
      { title: 'Tiktok Finance', href: '/finance/tiktok', icon: null, color: '' },
      { title: 'Tiktok2 Finance', href: '/finance/tiktok2', icon: null, color: '' },
      { title: 'Offline Finance', href: '/finance/offline', icon: null, color: '' },
      { title: 'Unpaid Orders', href: '/finance/unpaid-orders', icon: null, color: '' },
    ],
  },
  {
    title: 'Analytics',
    icon: <BarChart3 className="w-5 h-5" />,
    color: 'pink',
    children: [
      { title: 'Sales Value', href: '/analytics/sales-value', icon: null, color: '' },
      { title: 'Gross Profit', href: '/analytics/gross-profit', icon: null, color: '' },
      { title: 'Reports', href: '/analytics', icon: null, color: '' },
    ],
  },
  {
    title: 'Master Data',
    icon: <Database className="w-5 h-5" />,
    color: 'indigo',
    children: [
      { title: 'Products', href: '/master/products', icon: null, color: '' },
      { title: 'Brands', href: '/master/brands', icon: null, color: '' },
      { title: 'Customers', href: '/master/customers', icon: null, color: '' },
      { title: 'Mapping', href: '/master/mapping', icon: null, color: '' },
    ],
  },
  {
    title: 'Retur',
    icon: <RotateCcw className="w-5 h-5" />,
    color: 'red',
    children: [
      { title: 'Retur Pembelian', href: '/retur/pembelian', icon: null, color: '' },
      { title: 'Retur Penjualan', href: '/retur/penjualan', icon: null, color: '' },
      { title: 'Retur Offline', href: '/retur/offline', icon: null, color: '' },
    ],
  },
  {
    title: 'Admin',
    icon: <Settings className="w-5 h-5" />,
    color: 'gray',
    children: [
      { title: 'Users', href: '/admin/users', icon: null, color: '' },
      { title: 'Roles', href: '/admin/roles', icon: null, color: '' },
    ],
  },
];

const colorClasses: Record<string, { bg: string; text: string; hover: string }> = {
  blue: { bg: 'bg-blue-500/10', text: 'text-blue-600', hover: 'hover:bg-blue-500/20' },
  purple: { bg: 'bg-purple-500/10', text: 'text-purple-600', hover: 'hover:bg-purple-500/20' },
  green: { bg: 'bg-green-500/10', text: 'text-green-600', hover: 'hover:bg-green-500/20' },
  orange: { bg: 'bg-orange-500/10', text: 'text-orange-600', hover: 'hover:bg-orange-500/20' },
  emerald: { bg: 'bg-emerald-500/10', text: 'text-emerald-600', hover: 'hover:bg-emerald-500/20' },
  pink: { bg: 'bg-pink-500/10', text: 'text-pink-600', hover: 'hover:bg-pink-500/20' },
  indigo: { bg: 'bg-indigo-500/10', text: 'text-indigo-600', hover: 'hover:bg-indigo-500/20' },
  red: { bg: 'bg-red-500/10', text: 'text-red-600', hover: 'hover:bg-red-500/20' },
  gray: { bg: 'bg-gray-500/10', text: 'text-gray-600', hover: 'hover:bg-gray-500/20' },
};

export function Sidebar() {
  const pathname = usePathname();
  const { isOpen } = useSidebarStore();
  const [expandedItems, setExpandedItems] = React.useState<string[]>([]);
  const [collapsed, setCollapsed] = React.useState(false);

  const toggleItem = (title: string) => {
    if (collapsed) {
      setCollapsed(false);
      setTimeout(() => {
        setExpandedItems([title]);
      }, 100);
    } else {
      setExpandedItems((prev) =>
        prev.includes(title)
          ? prev.filter((item) => item !== title)
          : [...prev, title]
      );
    }
  };

  if (!isOpen) return null;

  return (
    <aside 
      className={`${
        collapsed ? 'w-20' : 'w-72'
      } bg-white border-r border-gray-200/60 min-h-screen transition-all duration-300 ease-in-out relative`}
    >
      {/* Logo Section */}
      <div className="h-16 px-6 border-b border-gray-100 flex items-center justify-between">
        {!collapsed && (
          <div className="flex items-center gap-3 animate-fade-in">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center shadow-md">
              <Sparkles className="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 className="text-lg font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                Liefmart
              </h1>
              <p className="text-[10px] text-gray-500 font-medium">ERP System</p>
            </div>
          </div>
        )}
        {collapsed && (
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center shadow-md mx-auto">
            <Sparkles className="w-5 h-5 text-white" />
          </div>
        )}
      </div>

      {/* Collapse Toggle */}
      <button
        onClick={() => setCollapsed(!collapsed)}
        className="absolute -right-3 top-20 w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-md hover:shadow-lg transition-all duration-200 z-10 group"
      >
        {collapsed ? (
          <ChevronsRight className="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-900" />
        ) : (
          <ChevronsLeft className="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-900" />
        )}
      </button>

      {/* Navigation */}
      <nav className="p-3 space-y-1 max-h-[calc(100vh-80px)] overflow-y-auto">
        {menuItems.map((item) => {
          const colors = colorClasses[item.color] || colorClasses.gray;
          const isActive = pathname === item.href || item.children?.some(child => pathname === child.href);
          
          return (
            <div key={item.title} className="mb-0.5">
              {item.children ? (
                <div>
                  <button
                    onClick={() => toggleItem(item.title)}
                    className={`w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group ${
                      collapsed ? 'justify-center' : ''
                    } ${
                      isActive 
                        ? `${colors.bg} ${colors.text}` 
                        : `text-gray-700 hover:bg-gray-50`
                    }`}
                    title={collapsed ? item.title : undefined}
                  >
                    <div className={`flex items-center ${collapsed ? '' : 'gap-3'}`}>
                      <div className={`${collapsed ? 'w-6 h-6' : 'w-8 h-8'} rounded-lg ${
                        isActive ? colors.bg : 'bg-gray-100 group-hover:bg-gray-200'
                      } flex items-center justify-center transition-colors duration-200 ${
                        isActive ? colors.text : 'text-gray-600'
                      }`}>
                        {item.icon}
                      </div>
                      {!collapsed && (
                        <>
                          <span className="group-hover:translate-x-0.5 transition-transform duration-200">
                            {item.title}
                          </span>
                        </>
                      )}
                    </div>
                    {!collapsed && (
                      expandedItems.includes(item.title) ? (
                        <ChevronDown className="w-4 h-4 text-gray-400 transition-transform duration-200" />
                      ) : (
                        <ChevronRight className="w-4 h-4 text-gray-400 transition-transform duration-200" />
                      )
                    )}
                  </button>
                  {!collapsed && expandedItems.includes(item.title) && (
                    <div className="ml-11 mt-1 space-y-0.5 animate-fade-in">
                      {item.children.map((child) => (
                        <Link
                          key={child.title}
                          href={child.href || '#'}
                          className={`block px-3 py-2 text-sm rounded-lg transition-all duration-200 ${
                            pathname === child.href
                              ? 'bg-blue-50/70 text-blue-700 font-medium'
                              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 hover:translate-x-1'
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
                  className={`flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group ${
                    collapsed ? 'justify-center' : 'gap-3'
                  } ${
                    isActive
                      ? `${colors.bg} ${colors.text}`
                      : 'text-gray-700 hover:bg-gray-50'
                  }`}
                  title={collapsed ? item.title : undefined}
                >
                  <div className={`${collapsed ? 'w-6 h-6' : 'w-8 h-8'} rounded-lg ${
                    isActive ? colors.bg : 'bg-gray-100 group-hover:bg-gray-200'
                  } flex items-center justify-center transition-colors duration-200 ${
                    isActive ? colors.text : 'text-gray-600'
                  }`}>
                    {item.icon}
                  </div>
                  {!collapsed && (
                    <span className="group-hover:translate-x-0.5 transition-transform duration-200">
                      {item.title}
                    </span>
                  )}
                </Link>
              )}
            </div>
          );
        })}
      </nav>
    </aside>
  );
}
