'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { Package, Tag, Users, GitBranch } from 'lucide-react';
import { Navbar } from '@/components/layout/navbar';
import { Sidebar } from '@/components/layout/sidebar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function MasterDataPage() {
  const router = useRouter();

  const masterDataTypes = [
    {
      title: 'Products',
      description: 'Kelola data produk dengan hierarchy lengkap',
      icon: Package,
      color: 'bg-blue-600',
      path: '/master/products',
      features: ['Brand', 'Sub-brand', 'Category', 'Type', 'Size', 'Variant'],
    },
    {
      title: 'Brands & Categories',
      description: 'Kelola brand, sub-brand, dan kategori produk',
      icon: Tag,
      color: 'bg-purple-600',
      path: '/master/brands',
      features: ['Brand', 'Sub Brand', 'Category', 'Type', 'Size', 'Variant'],
    },
    {
      title: 'Customers',
      description: 'Kelola data customer dan informasi kontak',
      icon: Users,
      color: 'bg-green-600',
      path: '/master/customers',
      features: ['Customer info', 'Tax ID', 'PKP status', 'Address'],
    },
    {
      title: 'Product Mapping',
      description: 'Mapping produk platform ke produk internal',
      icon: GitBranch,
      color: 'bg-orange-600',
      path: '/master/mapping',
      features: ['Platform products', 'Internal products', 'Auto-mapping'],
    },
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
                <h1 className="text-3xl font-bold tracking-tight">Master Data</h1>
                <p className="text-muted-foreground">
                  Kelola data master untuk produk, brand, customer, dan mapping
                </p>
              </div>

              <div className="grid gap-6 md:grid-cols-2">
                {masterDataTypes.map((type) => {
                  const Icon = type.icon;
                  return (
                    <Card
                      key={type.path}
                      className="cursor-pointer hover:shadow-lg transition-shadow"
                      onClick={() => router.push(type.path)}
                    >
                      <CardHeader>
                        <div className="flex items-center gap-4">
                          <div className={`${type.color} p-3 rounded-lg`}>
                            <Icon className="w-6 h-6 text-white" />
                          </div>
                          <div className="flex-1">
                            <CardTitle>{type.title}</CardTitle>
                            <CardDescription className="mt-1">
                              {type.description}
                            </CardDescription>
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <div className="space-y-3">
                          <div className="flex flex-wrap gap-2">
                            {type.features.map((feature) => (
                              <span
                                key={feature}
                                className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                              >
                                {feature}
                              </span>
                            ))}
                          </div>
                          <Button
                            variant="outline"
                            className="w-full"
                            onClick={(e) => {
                              e.stopPropagation();
                              router.push(type.path);
                            }}
                          >
                            Buka
                          </Button>
                        </div>
                      </CardContent>
                    </Card>
                  );
                })}
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Informasi Master Data</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3 text-sm text-muted-foreground">
                    <div>
                      <strong>Products:</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Hierarchical structure: Brand → Sub-brand → Category → Type → Size → Variant</li>
                        <li>Initial price versioning</li>
                        <li>Stock tracking</li>
                        <li>Export to Excel/CSV/PDF</li>
                      </ul>
                    </div>
                    <div>
                      <strong>Brands & Categories:</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Cascading dropdowns untuk product form</li>
                        <li>CRUD operations untuk semua level</li>
                      </ul>
                    </div>
                    <div>
                      <strong>Customers:</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Customer information management</li>
                        <li>Tax ID dan PKP status</li>
                        <li>Untuk offline sales dan invoicing</li>
                      </ul>
                    </div>
                    <div>
                      <strong>Product Mapping:</strong>
                      <ul className="list-disc list-inside ml-4 mt-1 space-y-1">
                        <li>Map platform products (Shopee, Tiktok) ke internal products</li>
                        <li>Auto-mapping feature</li>
                        <li>Check duplikasi</li>
                      </ul>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}
