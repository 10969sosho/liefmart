# API_REFERENCE.md

## Web Routes (Blade-based)

Semua endpoint di bawah adalah web routes yang me-return view atau redirect.

### Authentication
| Method | URL | Name | Middleware |
|---|---|---|---|
| GET | `/login` | login | guest |
| POST | `/login` | - | guest |
| POST | `/logout` | logout | auth |
| GET | `/register` | register | guest |
| POST | `/register` | - | guest |
| GET | `/password/reset` | password.request | guest |
| POST | `/password/email` | password.email | guest |
| POST | `/password/reset` | password.update | guest |
| GET | `/password/confirm` | password.confirm | auth |
| GET | `/email/verify` | verification.notice | auth |

### Dashboard & Home
| Method | URL | Name | Middleware |
|---|---|---|---|
| GET | `/` | - | redirect ke login |
| GET | `/home` | home | auth, main.category |
| GET | `/dashboard` | dashboard | auth, main.category |
| GET | `/maintenance` | maintenance | auth |
| GET | `/under-construction` | under-construction | auth |

### User Profile
| Method | URL | Name | Middleware |
|---|---|---|---|
| GET | `/profile` | users.profile | auth |
| PUT | `/profile` | users.profile.update | auth |

### Admin Management (superadmin only)
| Method | URL | Name | Middleware |
|---|---|---|---|
| GET | `/admin/roles` | admin.roles.index | auth, permissions |
| GET | `/admin/roles/create` | admin.roles.create | auth |
| POST | `/admin/roles` | admin.roles.store | auth |
| GET | `/admin/roles/{role}` | admin.roles.show | auth |
| GET | `/admin/roles/{role}/edit` | admin.roles.edit | auth |
| PUT | `/admin/roles/{role}` | admin.roles.update | auth |
| DELETE | `/admin/roles/{role}` | admin.roles.destroy | auth |
| POST | `/admin/roles/{role}/toggle-status` | admin.roles.toggle-status | auth |
| GET | `/admin/users` | admin.users.index | auth |
| GET | `/admin/users/create` | admin.users.create | auth |
| POST | `/admin/users` | admin.users.store | auth |
| GET | `/admin/users/{user}` | admin.users.show | auth |
| GET | `/admin/users/{user}/edit` | admin.users.edit | auth |
| PUT | `/admin/users/{user}` | admin.users.update | auth |
| DELETE | `/admin/users/{user}` | admin.users.destroy | auth |
| POST | `/admin/users/{user}/toggle-status` | admin.users.toggle-status | auth |
| GET | `/admin/permissions` | admin.permissions.index | auth |
| POST | `/admin/permissions` | admin.permissions.store | auth |
| GET | `/admin/permissions/{permission}/edit` | admin.permissions.edit | auth |
| PUT | `/admin/permissions/{permission}` | admin.permissions.update | auth |
| DELETE | `/admin/permissions/{permission}` | admin.permissions.destroy | auth |

### Database Restore (superadmin)
| Method | URL | Name |
|---|---|---|
| GET | `/database-restore` | database-restore.index |
| POST | `/database-restore` | database-restore.restore |
| POST | `/database-restore/server` | database-restore.server |
| GET | `/database-restore/download-backup` | database-restore.download-backup |
| POST | `/chunked-upload` | chunked-upload.chunk |
| POST | `/chunked-upload/merge` | chunked-upload.merge |

### Penerimaan (Goods Receipt)
| Method | URL | Name | Permission |
|---|---|---|---|
| GET | `/penerimaan` | penerimaan.index | warehouse.view |
| GET | `/penerimaan/create` | penerimaan.create | warehouse.create |
| POST | `/penerimaan/store` | penerimaan.store | warehouse.create |
| GET | `/penerimaan/{id}` | penerimaan.show | warehouse.view |
| GET | `/penerimaan/{id}/edit` | penerimaan.edit | warehouse.edit |
| PUT | `/penerimaan/{id}` | penerimaan.update | warehouse.edit |
| DELETE | `/penerimaan/{id}` | penerimaan.destroy | warehouse.edit |
| GET | `/penerimaan/{id}/print` | penerimaan.print | warehouse.view |
| GET | `/penerimaan/export` | penerimaan.export | warehouse.view |
| GET | `/penerimaan/export-detail` | penerimaan.export-detail | warehouse.view |
| GET | `/penerimaan/get-products` | penerimaan.get-products | warehouse.view |
| GET | `/penerimaan/get-tax-categories` | penerimaan.get-tax-categories | warehouse.view |
| POST | `/penerimaan/create-header` | penerimaan.create-header | warehouse.create |
| POST | `/penerimaan/{id}/store-batch-details` | penerimaan.store-batch-details | warehouse.create |
| POST | `/penerimaan/{id}/finalize` | penerimaan.finalize | warehouse.create |
| POST | `/penerimaan/{id}/update-header` | penerimaan.update-header | warehouse.edit |
| POST | `/penerimaan/{id}/clear-details` | penerimaan.clear-details | warehouse.edit |
| POST | `/penerimaan/{id}/finalize-update` | penerimaan.finalize-update | warehouse.edit |
| GET | `/penerimaan/price-history/{productId}` | penerimaan.price-history | warehouse.view |

### Warehouse / Stock
| Method | URL | Name | Permission |
|---|---|---|---|
| GET | `/warehouse` | warehouse.index | warehouse.view |
| GET | `/warehouse/create` | warehouse.create | warehouse.create |
| POST | `/warehouse/store` | warehouse.store | warehouse.create |
| GET | `/warehouse/stock/list` | warehouse.stock.list | warehouse.view |
| GET | `/warehouse/stock/damaged` | warehouse.stock.damaged | warehouse.view |
| GET | `/warehouse/stock/analytics` | warehouse.stock.analytics | warehouse.view |
| GET | `/warehouse/stock/export` | warehouse.stock.export | exports.warehouse |
| POST | `/warehouse/stock/export-selected` | warehouse.stock.export-selected | exports.warehouse |
| GET | `/warehouse/export` | warehouse.export | warehouse.export |

### Sales
| Method | URL | Name | Permission |
|---|---|---|---|
| GET | `/sales` | sales.index | sales.view |
| GET | `/sales/choose-type` | sales.choose-type | sales.view |
| GET | `/sales/offline` | sales.offline | sales.offline |

**Offline Sales:**
| Method | URL | Name | Permission |
|---|---|---|---|
| GET | `/sales/offline/list` | sales.offline.list | sales.view |
| GET | `/sales/offline/create` | sales.offline.create | sales.create |
| POST | `/sales/offline/store` | sales.offline.store | sales.create |
| GET | `/sales/offline/{offlineSale}` | sales.offline.show | sales.view |
| GET | `/sales/offline/{offlineSale}/print/invoice` | sales.offline.print.invoice | sales.view |
| GET | `/sales/offline/{offlineSale}/print/sj` | sales.offline.print.sj | sales.view |
| DEL | `/sales/offline/{offlineSale}` | sales.offline.destroy | sales.delete |
| POST | `/sales/offline/generate-sj-number` | sales.offline.generate-sj-number | sales.create |

**Online Sales:**
| Method | URL | Name |
|---|---|---|
| GET | `/sales/online` | sales.online |
| GET | `/sales/platform/{platform}` | sales.platform |
| GET | `/sales/online-input/{platform}` | sales.online-input |
| POST | `/sales/save-online-transaction` | sales.save-online-transaction |
| GET | `/sales/list` | sales.list |
| GET | `/sales/orders/{order}/detail` | sales.order.detail |
| GET | `/sales/orders/{order}/print` | sales.order.print |
| DEL | `/sales/orders/{order}` | sales.order.destroy (superadmin only) |

**Platform Import Routes:**
- Shopee: `/sales/shopee/import-excel`, `/sales/shopee/preview-import`, `/sales/shopee/process-import`
- Shopee2: `/sales/shopee2/import-excel`, `/sales/shopee2/preview-import`, `/sales/shopee2/process-import`
- TikTok: `/sales/tiktok/import-excel`, `/sales/tiktok/preview-import`, `/sales/tiktok/process-import`
- TikTok2: `/sales/tiktok2/import-excel`, `/sales/tiktok2/preview-import`, `/sales/tiktok2/process-import`

### Finance
| Method | URL | Name | Permission |
|---|---|---|---|
| GET | `/finance` | finance.index | finance.view |
| GET | `/finance/choose` | finance.choose | finance.view |

**Finance Platform Routes (Shopee/TikTok masing-masing 2 akun):**
Semua platform memiliki struktur route yang identik:
- `GET /finance/{platform}/` — index
- `GET /finance/{platform}/import` — form import
- `POST /finance/{platform}/import/preview` — preview import
- `GET /finance/{platform}/import/preview` — show preview
- `POST /finance/{platform}/import/process` — process import
- `GET /finance/{platform}/manual` — form manual
- `POST /finance/{platform}/manual-store` — store manual
- `DELETE /finance/{platform}/{id}` — delete
- `POST /finance/{platform}/adjust/{id}` — adjust
- `GET /finance/{platform}/print-invoice/{id}` — print invoice
- `GET /finance/{platform}/history/{id}` — history
- `POST /finance/{platform}/lock/{id}` — lock
- `POST /finance/{platform}/unlock/{id}` — unlock
- `GET /finance/{platform}/export/excel` — export Excel
- `GET /finance/{platform}/export/pdf` — export PDF

Platform tersedia: `shopee`, `shopee2`, `tiktok`, `tiktok2`

**Arus Kas Routes:**
- `GET/POST /finance/aruskasshopee/{action}` — Arus Kas Shopee
- `GET/POST /finance/aruskastiktok/{action}` — Arus Kas TikTok
- `GET/POST /finance/aruskasshopee2/{action}` — Arus Kas Shopee2
- `GET/POST /finance/aruskastiktok2/{action}` — Arus Kas TikTok2

**Finance Offline:**
| Method | URL | Name | Permission |
|---|---|---|---|
| GET | `/finance/offline` | finance.offline.index | finance.view |
| GET | `/finance/offline/invoices` | finance.offline.invoices | finance.view |
| GET | `/finance/offline/export` | finance.offline.export | finance.view |
| POST | `/finance/offline/pay/{id}` | finance.offline.pay | finance.view |
| POST | `/finance/offline/adjust-payment/{id}` | finance.offline.adjust-payment | finance.view |
| GET | `/finance/offline/generate-invoice/{saleId}` | finance.offline.generate-invoice | finance.view |
| GET | `/finance/offline/print-invoice/{id}` | finance.offline.print-invoice | check.print.permission |
| GET | `/finance/offline/print-invoice-after-return/{inv}` | finance.offline.print-invoice-after-return | auth |
| GET | `/finance/offline/print-return-invoice/{inv}` | finance.offline.print-return-invoice | auth |
| POST | `/finance/offline/approve-reprint/{id}` | finance.offline.approve-reprint | superadmin |
| DEL | `/finance/offline/delete-payment/{paymentId}` | finance.offline.delete-payment | auth |

### Unpaid Orders
| Method | URL | Name | Permission |
|---|---|---|---|
| GET | `/financial/unpaid-orders` | finance.unpaid-orders.index | finance.view |
| GET | `/financial/unpaid-orders/export/excel` | finance.unpaid-orders.export.excel | finance.view |
| GET | `/financial/unpaid-orders/export/pdf` | finance.unpaid-orders.export.pdf | finance.view |

### Analytics
| Method | URL | Name | Permission |
|---|---|---|---|
| GET | `/analytics` | analytics.index | analytics.view |
| GET | `/analytics/sales-value-report` | analytics.sales-value-report | analytics.view |
| GET | `/analytics/sales-volume-report` | analytics.sales-volume-report | analytics.view |
| GET | `/analytics/gross-profit-report` | analytics.gross-profit-report | analytics.view |
| GET | `/analytics/single-item-report` | analytics.single-item-report | analytics.view |
| GET | `/analytics/multiple-item-report` | analytics.multiple-item-report | analytics.view |
| GET | `/analytics/daily-sales-report` | analytics.daily-sales-report | analytics.view |
| GET | `/analytics/discount-analysis-report` | analytics.discount-analysis-report | analytics.view |
| GET | `/analytics/sales-by-platform` | analytics.sales-by-platform | analytics.view |
| GET | `/analytics/sales-detail-report` | analytics.sales-detail-report | analytics.view |
| GET | `/analytics/monthly-sales-summary` | analytics.monthly-sales-summary | analytics.view |
| GET | `/analytics/sales-by-day-of-week` | analytics.sales-by-day-of-week | analytics.view |
| GET | `/analytics/sales-by-date-number` | analytics.sales-by-date-number | analytics.view |
| GET | `/analytics/sales-by-status-day` | analytics.sales-by-status-day | analytics.view |
| GET | `/analytics/sales-by-master-product` | analytics.sales-by-master-product | analytics.view |
| GET | `/analytics/sales-by-master-product-special` | analytics.sales-by-master-product-special | analytics.view |
| GET | `/analytics/sales-by-platform-product` | analytics.sales-by-platform-product | analytics.view |
| GET | `/analytics/produk-platform-terlaris` | analytics.produk-platform-terlaris | analytics.view |
| GET | `/analytics/produk-internal-terlaris` | analytics.produk-internal-terlaris | analytics.view |

**Offline Analytics:**
| Method | URL | Name |
|---|---|---|
| GET | `/analytics/offline` | analytics.offline.index |
| GET | `/analytics/offline/monthly-sales-summary` | analytics.offline.monthly-sales-summary |
| GET | `/analytics/offline/sales-by-customer` | analytics.offline.sales-by-customer |
| GET | `/analytics/offline/sales-detail-report` | analytics.offline.sales-detail-report |
| GET | `/analytics/offline/sales-by-product` | analytics.offline.sales-by-product |
| GET | `/analytics/offline/gross-profit` | analytics.offline.gross-profit |

**Finance Analytics:**
| Method | URL |
|---|---|
| GET | `/analytics/finance/shopee` |
| GET | `/analytics/finance/shopee2` |
| GET | `/analytics/finance/tiktok` |
| GET | `/analytics/finance/tiktok2` |

### Master Data
| Method | URL | Permission |
|---|---|---|
| GET/POST/PUT/DEL | `/master/bank-accounts` | master.view |
| GET/POST/PUT/DEL | `/master/barang-platform` | master.view |
| GET/POST/PUT/DEL | `/master/mapping` | master.view |
| GET/POST/PUT/DEL | `/brands` | master.view |
| GET/POST/PUT/DEL | `/subbrands` | master.view |
| GET/POST/PUT/DEL | `/product-categories` | master.view |
| GET/POST/PUT/DEL | `/product-types` | master.view |
| GET/POST/PUT/DEL | `/product-sizes` | master.view |
| GET/POST/PUT/DEL | `/product-variants` | master.view |
| GET/POST/PUT/DEL | `/products` | master.view |
| GET/POST/PUT/DEL | `/customers` | master.view |

### Retur
| Method | URL | Permission |
|---|---|---|
| GET/POST/PUT/DEL | `/retur-pembelian` | warehouse.view |
| GET/POST/PUT/DEL | `/retur-penjualan` | sales.view |
| GET/POST/PUT/DEL | `/retur-offline` | sales.offline |

## API Routes (JSON)

| Method | URL | Auth |
|---|---|---|
| GET | `/api/user` | Sanctum |
| GET | `/api/tax-categories` | - |
| GET | `/api/products` | - |
| GET | `/api/products/{product}/stock-info` | - |
| GET | `/api/check-order` | - |
| POST | `/api/brands` | - |
| POST | `/api/sub-brands` | - |
| POST | `/api/product-categories` | - |
| POST | `/api/product-types` | - |
| POST | `/api/product-sizes` | - |
| POST | `/api/product-variants` | - |

### Filament Admin Panel
| URL | Description |
|---|---|
| `/admin` | Filament admin panel dashboard |
| `/admin/login` | Filament login |
| `/admin/products` | Product CRUD (Filament) |
| `/admin/product-categories` | Product categories CRUD |
| `/admin/brands` | Brands CRUD |
| `/admin/bank-accounts` | Bank accounts CRUD |
| `/admin/penerimaan` | Penerimaan CRUD (Filament) |
| `/admin/customers` | Customers CRUD |
| *(dan resource lainnya)* | |
