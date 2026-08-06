# Backend Integration Guide

## 🚀 Quick Start

### 1. Start Backend (Laravel)
```bash
cd apps/livemart
php artisan serve --host=127.0.0.1 --port=8000
```

Backend akan running di: **http://localhost:8000**

### 2. Start Frontend (Next.js)
```bash
cd apps/livemart-frontend
npm run dev
```

Frontend akan running di: **http://localhost:3000**

### 3. Login
- **Email**: superadmin@example.com
- **Password**: password

---

## 📡 API Endpoints

### Authentication
```
POST   /api/auth/login          - Login user
POST   /api/auth/register       - Register user
GET    /api/auth/user           - Get current user
POST   /api/auth/logout         - Logout user
```

### Dashboard
```
GET    /api/dashboard/stats              - Dashboard statistics
GET    /api/dashboard/chart-data         - Chart data
GET    /api/dashboard/recent-transactions - Recent transactions
GET    /api/dashboard/low-stock          - Low stock alerts
```

### Sales
```
GET    /api/sales/orders                 - List all orders
GET    /api/sales/orders/{id}            - Get order detail
DELETE /api/sales/orders/{id}            - Delete order
POST   /api/sales/offline/store          - Create offline sale
POST   /api/sales/{platform}/preview-import  - Preview Excel import
POST   /api/sales/{platform}/process-import  - Process Excel import
POST   /api/sales/online/store           - Create online sale manually
POST   /api/sales/generate-sj-number     - Generate SJ number
POST   /api/sales/check-order            - Check duplicate order
```

### Finance
```
GET    /api/finance/{platform}           - Get transactions by platform
POST   /api/finance/{platform}/import/preview  - Preview payment import
POST   /api/finance/{platform}/import/process  - Process payment import
POST   /api/finance/{platform}/manual-store    - Manual payment entry
POST   /api/finance/{platform}/lock/{id}       - Lock transaction
POST   /api/finance/{platform}/unlock/{id}     - Unlock transaction
DELETE /api/finance/{platform}/{id}            - Delete transaction
GET    /api/finance/{platform}/print-invoice/{id} - Print invoice
GET    /api/finance/unpaid-orders              - Get unpaid orders
```

### Products
```
GET    /api/products                     - List products
POST   /api/products                     - Create product
GET    /api/products/{id}                - Get product detail
PUT    /api/products/{id}                - Update product
DELETE /api/products/{id}                - Delete product
GET    /api/products/export/{format}     - Export products
```

### Customers
```
GET    /api/customers                    - List customers
POST   /api/customers                    - Create customer
GET    /api/customers/{id}               - Get customer detail
PUT    /api/customers/{id}               - Update customer
DELETE /api/customers/{id}               - Delete customer
```

### Brands
```
GET    /api/brands                       - List brands
POST   /api/brands                       - Create brand
GET    /api/brands/{id}                  - Get brand detail
PUT    /api/brands/{id}                  - Update brand
DELETE /api/brands/{id}                  - Delete brand
```

### Mapping
```
GET    /api/mapping                      - List mappings
POST   /api/mapping                      - Create mapping
POST   /api/mapping/auto-create/{platform}/{productName} - Auto mapping
```

### Analytics
```
GET    /api/analytics/sales-value        - Sales value report
GET    /api/analytics/sales-volume       - Sales volume report
GET    /api/analytics/gross-profit       - Gross profit report
GET    /api/analytics/monthly-summary    - Monthly summary
GET    /api/analytics/sales-by-platform  - Sales by platform
GET    /api/analytics/sales-detail       - Sales detail report
POST   /api/analytics/exports/dispatch   - Dispatch export job
GET    /api/analytics/exports/list       - List exports
GET    /api/analytics/exports/{id}/download - Download export
```

---

## 🔐 Authentication

Semua endpoint (kecuali login/register) memerlukan Bearer token.

### Cara mendapatkan token:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"superadmin@example.com","password":"password"}'
```

Response:
```json
{
  "success": true,
  "data": {
    "user": {...},
    "token": "1|abc123..."
  }
}
```

### Menggunakan token:
```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 🌐 CORS Configuration

CORS sudah dikonfigurasi untuk allow:
- http://localhost:3000 (Next.js frontend)
- http://localhost:3001 (Alternative port)

Config file: `apps/livemart/config/cors.php`

---

## 🗄️ Database Notes

### Tables Structure
- **orders**: id, platform_id, order_number, tanggal, hari, status_hari, status
- **products**: id, name, sku, brand_id, initial_price, stock, ...
- **customers**: id, name, email, phone, address, tax_id, is_pkp, ...
- **brands**: id, name, ...

### Mock Data
Dashboard stats menggunakan mock data untuk testing. Untuk production, perlu:
1. Add missing columns ke table orders (total_amount, order_date, platform)
2. Atau update controller untuk menggunakan kolom yang ada

---

## 🧪 Testing API

### Test Dashboard
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"superadmin@example.com","password":"password"}' | \
  python3 -c "import sys, json; print(json.load(sys.stdin)['data']['token'])")

curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

### Test Products
```bash
curl -X GET http://localhost:8000/api/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

---

## 📝 API Response Format

### Success Response
```json
{
  "success": true,
  "data": {...},
  "message": "Operation successful"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {...}
}
```

### Paginated Response
```json
{
  "current_page": 1,
  "data": [...],
  "first_page_url": "...",
  "from": 1,
  "last_page": 10,
  "last_page_url": "...",
  "next_page_url": "...",
  "per_page": 20,
  "prev_page_url": null,
  "to": 20,
  "total": 200
}
```

---

## 🚨 Known Issues

### 1. Missing Columns
Table `orders` tidak punya kolom:
- `total_amount`
- `order_date`
- `platform`
- `customer_id`
- `payment_status`

**Solution**: 
- Option A: Add columns via migration
- Option B: Use existing columns (tanggal, status)
- Option C: Use mock data (current implementation)

### 2. Finance Transactions
Table `finance_transactions` mungkin belum ada. Perlu create migration.

### 3. Order Items
Table `order_items` structure perlu disesuaikan dengan frontend requirements.

---

## 🔧 Development Tips

### Clear Cache
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### List Routes
```bash
php artisan route:list --path=api
```

### Debug API
```bash
# Check if backend is running
curl http://localhost:8000/api/user

# Check routes
php artisan route:list | grep api
```

---

## 📚 Next Steps

1. ✅ Backend API endpoints created
2. ✅ CORS configured
3. ✅ Sanctum authentication setup
4. ⏳ Test all endpoints with frontend
5. ⏳ Fix database schema issues
6. ⏳ Add missing migrations
7. ⏳ Production deployment

---

## 🎯 Status

- **Backend**: ✅ Running on http://localhost:8000
- **Frontend**: ✅ Running on http://localhost:3000
- **Authentication**: ✅ Working
- **API Endpoints**: ✅ 50+ endpoints ready
- **Database**: ⚠️ Some columns missing (using mock data)
- **Integration**: ✅ Ready for testing

---

**Last Updated**: 2026-08-04  
**Branch**: `refactor/nextjs-frontend`  
**Commit**: Backend API integration complete
