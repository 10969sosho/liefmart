{{-- resources/views/components/sidebar.blade.php --}}

<div class="sidebar">
    <div class="sticky-top h-100 d-flex flex-column">
        <!-- Logo and branding -->
        <div class="sidebar-header p-3 border-bottom d-flex align-items-center" style="border-color: rgba(0,0,0,0.05) !important;">
            <div class="d-flex align-items-center">
                <div class="brand-icon rounded-circle d-flex align-items-center justify-content-center bg-primary me-2"
                     style="width: 34px; height: 34px;">
                    <i class="fas fa-cube text-white" style="font-size: 0.9rem;"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-semibold text-dark" style="font-size: 1rem; letter-spacing: -0.02em;">Admin Dashboard</h5>
                    <p class="mb-0 text-muted" style="font-size: 0.7rem;">Inventory Management</p>
                </div>
            </div>
        </div>

        <!-- Navigation menu -->
        <div class="sidebar-content p-2 flex-grow-1 overflow-auto">
            <div class="sidebar-nav">
                <!-- Main Navigation -->
                <div class="nav-section mb-3">
                    <p class="nav-section-title text-uppercase mb-2 ms-3" style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px; color: #1F2937;">
                        Main Navigation
                    </p>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active py-2 px-3 rounded" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Inventory Management -->
                <div class="nav-section mb-3">
                    <p class="nav-section-title text-uppercase mb-2 ms-3" style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px; color: #1F2937;">
                        Inventory
                    </p>
                    
                    <ul class="nav flex-column">
                        <!-- Penerimaan Barang - Direct link -->
                        <li class="nav-item mb-1">
                            <a class="nav-link py-2 px-3 rounded" href="{{ route('penerimaan.index') }}">
                                    <i class="fas fa-truck-loading me-2"></i> Goods Receipt
                            </a>
                        </li>
                        
                        <!-- Warehouse dropdown -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseWarehouse" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-warehouse me-2"></i> Warehouse
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseWarehouse">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('warehouse.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Item Transfers
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('warehouse.stock.list') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Stock List
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('warehouse.stock.damaged') }}">
                                            <i class="fas fa-circle fa-xs me-2 text-danger"></i> Barang Rusak
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <!-- Sales Management -->
                <div class="nav-section mb-3">
                    <p class="nav-section-title text-uppercase mb-2 ms-3" style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px; color: #1F2937;">
                        Sales
                    </p>
                    
                    <ul class="nav flex-column">
                        <!-- Penjualan dropdown -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapsePenjualan" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-shopping-cart me-2"></i> Sales
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapsePenjualan">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('sales.choose-type') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Create Sale
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('sales.list') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales List Online
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('sales.offline.list') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales List Offline
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('sales.outgoing-items') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Outgoing Items
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        
                        <!-- Finance dropdown -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseFinanceOffline" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-file-invoice-dollar me-2"></i> Finance Offline
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseFinanceOffline">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('finance.offline.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Index Barang Penjualan
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('finance.offline.invoices') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> List Invoice Offline
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Finance Online -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseFinanceOnline" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-money-bill-wave me-2"></i> Finance Online
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseFinanceOnline">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('finance.choose') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Choose Platform
                                        </a>
                                    </li>
                                   
                                </ul>
                            </div>
                        </li>
                        
                        <!-- Retur dropdown -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseReturBarang" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-undo-alt me-2"></i> Returns
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseReturBarang">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('retur-pembelian.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Purchase Returns
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('retur-penjualan.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Online Sales Returns
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('retur-offline.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Offline Sales Returns
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <!-- Analytics Section - Restructured -->
                <div class="nav-section mb-3">
                    <p class="nav-section-title text-uppercase mb-2 ms-3" style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px; color: #1F2937;">
                        Analytics
                    </p>
                    
                    <ul class="nav flex-column">
                        <!-- General Analytics -->
                        <li class="nav-item mb-1">
                            <a class="nav-link py-2 px-3 rounded" href="{{ route('warehouse.stock.analytics') }}">
                                <i class="fas fa-chart-bar me-2"></i> Stock Analytics
                            </a>
                        </li>
                        
                        <!-- Finance Analytics - NEW SECTION -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseFinanceAnalytics" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-money-bill-wave me-2"></i> Analytic Finance
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseFinanceAnalytics">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.finance.shopee') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Analytic Shopee
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.finance.tokopedia') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Analytic Tokopedia
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.finance.tiktok') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Analytic Tiktok
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.finance.blibli') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Analytic Blibli
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        
                        <!-- Online Analytics -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseOnlineAnalytics" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-globe me-2"></i> Online Analytics
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseOnlineAnalytics">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.sales-by-platform') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales Report
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.sales-detail-report') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales Detail Report
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.sales-by-day-of-week') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales by Day of Week
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.sales-by-date-number') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales by Date 
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.sales-by-status-day') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales by Status & Day
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.monthly-sales-summary') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Monthly Sales Summary
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.sales-by-master-product') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Gross Profit Master
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.sales-by-platform-product') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Gross Profit Platform
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        
                        <!-- Offline Analytics -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseOfflineAnalytics" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-store me-2"></i> Offline Analytics
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseOfflineAnalytics">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.offline.monthly-sales-summary') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Monthly Sales Summary
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.offline.sales-by-customer') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales by Customer
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.offline.sales-detail-report') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales Detail Report
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.offline.sales-by-product') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sales by Product
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('analytics.offline.gross-profit') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Gross Profit
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <!-- Master Data -->
                <div class="nav-section mb-3">
                    <p class="nav-section-title text-uppercase mb-2 ms-3" style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px; color: #1F2937;">
                        Master Data
                    </p>
                    
                    <ul class="nav flex-column">
                        <!-- Brands -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseBrands" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-tag me-2"></i> Brands
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseBrands">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('brands.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> All Brands
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('brands.create') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Add New Brand
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Sub Brands -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseSubBrands" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-tags me-2"></i> Sub Brands
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseSubBrands">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('subbrands.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> All Sub Brands
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('subbrands.create') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Add Sub Brand
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Product Categories -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseCategories" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-sitemap me-2"></i> Product Categories
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseCategories">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('product-categories.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Categories
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('product-types.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Types
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('product-sizes.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Sizes
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('product-variants.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Variants
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Products Management -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseProducts" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-box me-2"></i> Products
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseProducts">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('products.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> All Products
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('products.create') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Add New Product
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>


                        <!-- Master Barang Platform -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseBarangPlatform" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-store me-2"></i> Barang Platform
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseBarangPlatform">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('barang-platform.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> All Barang Platform
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('barang-platform.create') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Create Barang Platform
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Product Mapping -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseMapping" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-link me-2"></i> Mapping Barang
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseMapping">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('master.mapping.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> All Mappings
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('master.mapping.create') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Create Mapping
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Customers -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseCustomers" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-users me-2"></i> Customers
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseCustomers">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('customers.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> All Customers
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('customers.create') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Add New Customer
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        
                        <!-- Bank Accounts -->
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               href="{{ route('bank-accounts.index') }}">
                                <div>
                                    <i class="fas fa-university me-2"></i> Bank Accounts
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Admin Management (berdasarkan permission) -->
                @if(Auth::check() && (Auth::user()->hasPermission('users.view') || Auth::user()->hasPermission('roles.view')))
                <div class="nav-section mb-3">
                    <p class="nav-section-title text-uppercase mb-2 ms-3" style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px; color: #1F2937;">
                        Admin Management
                    </p>
                    
                    <ul class="nav flex-column">
                        <!-- Role Management -->
                        @if(Auth::user()->hasPermission('roles.view'))
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseRoles" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-user-shield me-2"></i> Role Management
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseRoles">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('admin.roles.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> All Roles
                                        </a>
                                    </li>
                                    @if(Auth::user()->hasPermission('roles.create'))
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('admin.roles.create') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Create Role
                                        </a>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                        @endif

                        <!-- User Management -->
                        @if(Auth::user()->hasPermission('users.view'))
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex justify-content-between align-items-center py-2 px-3 rounded" 
                               data-bs-toggle="collapse" href="#collapseUserAdmin" role="button" aria-expanded="false">
                                <div>
                                    <i class="fas fa-users-cog me-2"></i> User Management
                                </div>
                                <i class="fas fa-chevron-right fa-xs transition-transform"></i>
                            </a>
                            <div class="collapse" id="collapseUserAdmin">
                                <ul class="nav flex-column ms-3 mt-1">
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('admin.users.index') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> All Users
                                        </a>
                                    </li>
                                    @if(Auth::user()->hasPermission('users.create'))
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3" href="{{ route('admin.users.create') }}">
                                            <i class="fas fa-circle fa-xs me-2"></i> Create User
                                        </a>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                        @endif

                        <!-- Database Restore (Superadmin only) -->
                        @if(auth()->check() && auth()->user()->role_id == 1)
                        <li class="nav-item mb-1">
                            <a class="nav-link py-2 px-3 rounded" href="{{ route('database-restore.index') }}">
                                <i class="fas fa-database me-2"></i> Database Restore
                            </a>
                        </li>
                        @endif

                        <!-- Legacy User Management (untuk kompatibilitas) -->
                      
                    </ul>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Sidebar footer with user info and logout -->
        <div class="sidebar-footer p-3 border-top mt-auto" style="border-color: rgba(0,0,0,0.05) !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="avatar rounded-circle bg-primary d-flex align-items-center justify-content-center me-2 text-white"
                         style="width: 32px; height: 32px; font-size: 0.8rem;">
                        {{ Auth::check() ? substr(Auth::user()->name, 0, 1) : 'G' }}
                    </div>
                    <div>
                        <p class="mb-0 small fw-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</p>
                        <p class="mb-0 small text-muted" style="font-size: 0.7rem;">{{ Auth::check() && Auth::user()->isSuperAdmin() ? 'Administrator' : 'User' }}</p>
                    </div>
                </div>
                <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <a href="{{ route('logout') }}" class="btn btn-link p-0 text-muted" 
                   onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Add this script to the bottom of your sidebar component or include it in your main JS file -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Make dropdown arrows rotate when expanded
        const dropdownToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                const icon = this.querySelector('.fa-chevron-right');
                
                // Toggle a class to control the rotation
                if (icon) {
                    if (this.getAttribute('aria-expanded') === 'true') {
                        icon.style.transform = 'rotate(90deg)';
                    } else {
                        icon.style.transform = 'rotate(0deg)';
                    }
                }
                
                // For nested dropdowns, prevent parent collapse from toggling
                if (this.closest('.collapse')) {
                    e.stopPropagation();
                }
            });
            
            // Initialize the rotation based on initial state
            const collapse = document.querySelector(toggle.getAttribute('href'));
            const icon = toggle.querySelector('.fa-chevron-right');
            
            if (icon && collapse && collapse.classList.contains('show')) {
                icon.style.transform = 'rotate(90deg)';
            }
        });
        
        // Ensure nested collapses work independently
        const nestedCollapses = document.querySelectorAll('.collapse .collapse');
        nestedCollapses.forEach(collapse => {
            collapse.addEventListener('show.bs.collapse', function(e) {
                e.stopPropagation();
            });
            
            collapse.addEventListener('hide.bs.collapse', function(e) {
                e.stopPropagation();
            });
        });
    });
    
    // Function to toggle submenu visibility
    function toggleSubMenu(id) {
        const submenu = document.getElementById(id);
        const isVisible = submenu.style.display !== 'none';
        
        // Hide all submenus first
        document.querySelectorAll('.submenu').forEach(menu => {
            menu.style.display = 'none';
            
            // Reset arrow rotation for all submenu toggles
            const parentLink = menu.previousElementSibling;
            if (parentLink) {
                const arrow = parentLink.querySelector('.fa-chevron-right');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        });
        
        // Toggle this submenu
        if (!isVisible) {
            submenu.style.display = 'block';
            
            // Rotate the arrow
            const parentLink = submenu.previousElementSibling;
            if (parentLink) {
                const arrow = parentLink.querySelector('.fa-chevron-right');
                if (arrow) arrow.style.transform = 'rotate(90deg)';
            }
        }
        
        // Prevent the event from bubbling up to parent elements
        event.stopPropagation();
    }
</script>

<style>
    .transition-transform {
        transition: transform 0.2s ease-in-out;
    }
    
    .sidebar .nav-link {
        color: #1F2937;
        transition: all 0.2s ease;
        border-radius: 0;
        margin: 2px 0;
        background-color: transparent;
    }
    
    .sidebar .nav-link:hover {
        background-color: transparent;
        color: #1F2937;
        transform: translateX(2px);
    }
    
    .sidebar .nav-link.active {
        background-color: transparent;
        color: #1F2937;
        font-weight: 500;
        box-shadow: none;
    }
    
    .sidebar .nav-link i {
        font-size: 0.9rem;
        width: 18px;
        text-align: center;
        color: #6366F1;
        opacity: 1;
    }
    
    .sidebar-content {
        max-height: calc(100vh - 135px);
    }

    .nav-section-title {
        color: #1F2937;
        padding-top: 0.75rem;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    
    .sidebar .collapse .nav-link {
        padding-left: 2rem;
        font-size: 0.85rem;
        opacity: 0.9;
        color: #1F2937;
    }
    
    .sidebar .collapse .nav-link:hover {
        color: #1F2937;
        background-color: transparent;
    }
    
    .sidebar .collapse .nav-link.active {
        color: #1F2937;
        background-color: transparent;
    }
    
    .sidebar .collapse .nav-link i {
        color: #6366F1;
    }
    
    /* Nested dropdown styles */
    .sidebar .collapse .collapse {
        display: none;
    }
    
    .sidebar .collapse .collapse.show {
        display: block;
    }
    
    .sidebar .collapse .collapse .nav-link {
        padding-left: 3rem;
        font-size: 0.8rem;
    }
    
    /* Submenu styles */
    .submenu {
        margin-left: 1rem;
        transition: all 0.3s ease;
    }
    
    .submenu .nav-link {
        padding-left: 2.5rem !important;
        font-size: 0.8rem !important;
    }
    
    /* Pointer cursor for clickable items */
    .nav-link[onclick] {
        cursor: pointer;
    }
    
    /* Transition for chevron rotation */
    .fa-chevron-right {
        transition: transform 0.3s ease;
    }
</style>
