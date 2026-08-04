# Liefmart Frontend - Next.js

Frontend aplikasi Liefmart ERP menggunakan Next.js 14+ dengan TypeScript dan Tailwind CSS.

## Stack Teknologi

- **Framework**: Next.js 14+ (App Router)
- **Language**: TypeScript
- **Styling**: Tailwind CSS v4
- **UI Components**: Shadcn/ui + Radix UI
- **State Management**: Zustand
- **Data Fetching**: TanStack Query (React Query)
- **Forms**: React Hook Form + Zod
- **HTTP Client**: Axios
- **Icons**: Lucide React
- **Charts**: Recharts

## Struktur Folder

```
src/
├── app/                    # Next.js App Router pages
│   ├── auth/              # Authentication pages (login, register)
│   ├── dashboard/         # Dashboard page
│   ├── penerimaan/        # Penerimaan module
│   ├── warehouse/         # Warehouse module
│   ├── sales/             # Sales module
│   ├── finance/           # Finance module
│   ├── analytics/         # Analytics & reports
│   ├── master/            # Master data module
│   ├── retur/             # Return module
│   ├── admin/             # Admin & user management
│   ├── layout.tsx         # Root layout
│   ├── page.tsx           # Root page (redirects to dashboard)
│   └── globals.css        # Global styles
│
├── components/            # React components
│   ├── ui/               # Base UI components (button, card, table, etc)
│   ├── layout/           # Layout components (navbar, sidebar)
│   └── shared/           # Shared/reusable components
│
├── lib/                   # Utilities & configurations
│   ├── api/              # API client & endpoints
│   │   ├── client.ts     # Axios instance with interceptors
│   │   ├── auth.ts       # Auth API
│   │   ├── dashboard.ts  # Dashboard API
│   │   ├── products.ts   # Products API
│   │   ├── sales.ts      # Sales API
│   │   ├── warehouse.ts  # Warehouse API
│   │   ├── penerimaan.ts # Penerimaan API
│   │   ├── finance.ts    # Finance API
│   │   ├── analytics.ts  # Analytics API
│   │   ├── retur.ts      # Retur API
│   │   └── admin.ts      # Admin API
│   ├── stores/           # Zustand stores
│   │   ├── auth.ts       # Auth store
│   │   └── sidebar.ts    # Sidebar store
│   ├── hooks/            # Custom React hooks
│   └── utils.ts          # Helper functions
│
└── types/                # TypeScript types & interfaces
    └── index.ts          # Global type definitions
```

## Setup & Installation

### Prerequisites

- Node.js 18+ atau 20+
- npm atau yarn atau pnpm

### Install Dependencies

```bash
cd apps/livemart-frontend
npm install
```

### Environment Variables

Buat file `.env.local`:

```env
# API Configuration
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1

# NextAuth Configuration
NEXTAUTH_URL=http://localhost:3000
NEXTAUTH_SECRET=your-secret-key-change-this-in-production

# Laravel Sanctum
NEXT_PUBLIC_SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

### Development

```bash
npm run dev
```

Aplikasi akan berjalan di `http://localhost:3000`

### Build untuk Production

```bash
npm run build
npm run start
```

### Lint & Type Check

```bash
npm run lint
```

## Fitur yang Sudah Diimplementasi

### ✅ Foundation & Setup
- [x] Next.js 14+ dengan App Router
- [x] TypeScript configuration
- [x] Tailwind CSS v4
- [x] Shadcn/ui components (Button, Card, Table, Input, Label)
- [x] Axios client dengan interceptors
- [x] Zustand stores (auth, sidebar)
- [x] React Query setup

### ✅ Authentication & Layout
- [x] Login page dengan form validation (Zod)
- [x] Auth store dengan persist
- [x] Navbar component (responsive)
- [x] Sidebar component dengan menu navigation
- [x] Protected routes setup

### ✅ Modules
- [x] **Dashboard**: Stats cards, sales by platform, recent transactions, low stock alerts
- [x] **Penerimaan**: List penerimaan dengan pagination, search, delete
- [x] **Warehouse**: Stock list, unlocated items, warehouse analytics

### 📋 API Endpoints (Siap Digunakan)
Semua API client sudah dibuat dan siap diintegrasikan dengan Laravel backend:
- Auth API (login, register, logout, forgot password)
- Dashboard API (stats, chart data, recent transactions)
- Products API (CRUD, export)
- Sales API (orders, online/offline sales, import Excel)
- Warehouse API (stock management, move stock, analytics)
- Penerimaan API (CRUD, batch details, finalize)
- Finance API (transactions, import, lock/unlock, invoice)
- Analytics API (reports, exports, queued exports)
- Retur API (purchase/sales returns, process, finance)
- Admin API (users, roles, permissions)

## Modules yang Perlu Dilanjutkan

### 🚧 High Priority
1. **Sales Module** (Online & Offline)
   - Choose type page
   - Online sales (Shopee, Tiktok) dengan Excel import
   - Offline sales manual input
   - Order list & detail
   - Print SJ functionality

2. **Finance Module** (Multi-platform)
   - Shopee/Shopee2/Tiktok/Tiktok2 Finance
   - Payment import & manual entry
   - Invoice generation (PKP/Non-PKP)
   - Lock/unlock transactions
   - Offline finance

3. **Analytics & Reports**
   - Sales value/volume reports
   - Gross profit report
   - Monthly summary
   - Sales by platform/product
   - Queued export system dengan notifications

### 🔧 Medium Priority
4. **Master Data**
   - Products CRUD dengan hierarchical categories
   - Brands, Sub-brands, Categories
   - Customers management
   - Product mapping (Platform → Internal)
   - Auto-mapping functionality

5. **Retur Management**
   - Retur Pembelian (to supplier)
   - Retur Penjualan (from customer online)
   - Retur Offline
   - Finance processing for returns

6. **Admin Panel**
   - User management
   - Role & permissions
   - Database backup/restore

## Integration dengan Laravel Backend

### API Endpoints yang Harus Dibuat di Laravel

Backend Laravel (`apps/livemart`) perlu membuat API endpoints berikut:

```php
// routes/api.php (prefix: /api/v1)

// Auth
POST   /auth/login
POST   /auth/register
POST   /auth/logout
GET    /auth/user
POST   /auth/forgot-password
POST   /auth/reset-password

// Dashboard
GET    /dashboard/stats
GET    /dashboard/chart-data
GET    /dashboard/recent-transactions
GET    /dashboard/low-stock

// Penerimaan
GET    /penerimaan
POST   /penerimaan/create-header
POST   /penerimaan/{id}/store-batch-details
POST   /penerimaan/{id}/finalize
GET    /penerimaan/{id}
PUT    /penerimaan/{id}
DELETE /penerimaan/{id}

// Warehouse
GET    /warehouse/stock
POST   /warehouse/move
GET    /warehouse/stock/analytics

// Sales
GET    /sales/orders
POST   /sales/offline/store
POST   /sales/online/store
POST   /sales/{platform}/preview-import
POST   /sales/{platform}/process-import

// Finance
GET    /finance/{platform}
POST   /finance/{platform}/import/preview
POST   /finance/{platform}/import/process
POST   /finance/{platform}/manual-store

// Analytics
GET    /analytics/sales-value
GET    /analytics/sales-volume
GET    /analytics/gross-profit
POST   /analytics/exports/dispatch
GET    /analytics/exports/{id}/download

// ... dan seterusnya
```

### Laravel Sanctum Setup

Pastikan Laravel Sanctum sudah dikonfigurasi untuk SPA authentication:

```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost:3000')),

// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'supports_credentials' => true,
```

## Architecture Patterns

### API Client Pattern
```typescript
// lib/api/client.ts
// Axios instance dengan interceptors untuk:
// - Auto-inject auth token
// - Handle 401 errors (redirect to login)
// - Global error handling
```

### State Management Pattern
```typescript
// Zustand untuk global state (auth, UI state)
// React Query untuk server state (API data)
```

### Component Pattern
```typescript
// 1. QueryClientProvider wrapper untuk setiap page
// 2. Separate content component untuk logic
// 3. Export default page component yang wraps content
```

## Styling Guidelines

- Gunakan Tailwind utility classes
- Responsive design (mobile-first)
- Consistent spacing (p-6, gap-4, space-y-6)
- Color scheme: Blue primary, Gray neutral, Green success, Red error
- Typography: Inter font family

## Next Steps untuk Development

### Phase 1: Core Sales & Finance (Week 1-2)
1. Implementasi Sales module (online & offline)
2. Implementasi Finance module (4 platforms)
3. Testing integration dengan Laravel backend

### Phase 2: Analytics & Reports (Week 3)
1. Implementasi semua report pages
2. Setup queued export system
3. Charts & visualizations

### Phase 3: Master Data & Admin (Week 4)
1. Implementasi Master Data CRUD
2. Implementasi Product Mapping
3. User & Role management

### Phase 4: Testing & Polish (Week 5)
1. End-to-end testing
2. Performance optimization
3. UI/UX refinement
4. Documentation

## Contributing

1. Buat branch baru dari `refactor/nextjs-frontend`
2. Commit changes dengan conventional commits
3. Test semua fungsi sebelum push
4. Create PR untuk review

## Notes

- Frontend ini TIDAK menggunakan Livewire atau Filament
- Semua pages adalah Next.js pages, bukan Blade templates
- API-first approach: semua data dari Laravel API
- SSR disabled untuk pages yang memerlukan auth (Client-side rendering)

## Status Proyek

✅ **Foundation Complete** (100%)  
✅ **Auth & Layout Complete** (100%)  
✅ **Dashboard Complete** (100%)  
✅ **Penerimaan Complete** (90% - perlu create/edit forms)  
✅ **Warehouse Complete** (80% - perlu move & analytics pages)  
⏳ **Sales Module** (0%)  
⏳ **Finance Module** (0%)  
⏳ **Analytics Module** (0%)  
⏳ **Master Data Module** (0%)  
⏳ **Retur Module** (0%)  
⏳ **Admin Module** (0%)  

**Overall Progress: ~30%**
