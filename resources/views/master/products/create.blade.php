@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>Tambah Produk Baru</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('products.store') }}" method="POST" id="productForm">
                        @csrf
                        
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-control-label">Nama Produk <span class="text-danger">*</span></label>
                                    <input class="form-control @error('name') is-invalid @enderror" type="text" 
                                        id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sku" class="form-control-label">SKU</label>
                                    <input class="form-control @error('sku') is-invalid @enderror" type="text" 
                                        id="sku" name="sku" value="{{ old('sku') }}">
                                    @error('sku')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="main_category_id" class="form-control-label">Main Category</label>
                                    <input type="text" class="form-control" value="{{ $mainCategories->first()->name }}" readonly>
                                    <input type="hidden" name="main_category_id" id="main_category_id" value="{{ $mainCategoryId }}">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="brand_id" class="form-control-label">Brand <span class="text-danger">*</span></label>
                                    <select class="form-select tom-select @error('brand_id') is-invalid @enderror" 
                                        id="brand_id" name="brand_id" required>
                                        <option value="">Pilih Brand</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('brand_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sub_brand_id" class="form-control-label">Sub Brand <span class="text-danger">*</span></label>
                                    <select class="form-select tom-select @error('sub_brand_id') is-invalid @enderror" 
                                        id="sub_brand_id" name="sub_brand_id" required>
                                        <option value="">Pilih Sub Brand</option>
                                        @foreach($subBrands as $subBrand)
                                            <option value="{{ $subBrand->id }}" 
                                                data-brand="{{ $subBrand->brand_id }}" 
                                                {{ old('sub_brand_id') == $subBrand->id ? 'selected' : '' }}>
                                                {{ $subBrand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sub_brand_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="product_category_id" class="form-control-label">Kategori Produk <span class="text-danger">*</span></label>
                                    <select class="form-select tom-select @error('product_category_id') is-invalid @enderror" 
                                        id="product_category_id" name="product_category_id" required>
                                        <option value="">Pilih Kategori Produk</option>
                                        @foreach($productCategories as $category)
                                            <option value="{{ $category->id }}" 
                                                data-subbrand="{{ $category->sub_brand_id }}" 
                                                {{ old('product_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="product_type_id" class="form-control-label">Tipe Produk <span class="text-danger">*</span></label>
                                    <select class="form-select tom-select @error('product_type_id') is-invalid @enderror" 
                                        id="product_type_id" name="product_type_id" required>
                                        <option value="">Pilih Tipe Produk</option>
                                        @foreach($productTypes as $type)
                                            <option value="{{ $type->id }}" 
                                                data-category="{{ $type->product_category_id }}" 
                                                {{ old('product_type_id') == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_type_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="product_size_id" class="form-control-label">Ukuran Produk <span class="text-danger">*</span></label>
                                    <select class="form-select tom-select @error('product_size_id') is-invalid @enderror" 
                                        id="product_size_id" name="product_size_id" required>
                                        <option value="">Pilih Ukuran Produk</option>
                                        @foreach($productSizes as $size)
                                            <option value="{{ $size->id }}" {{ old('product_size_id') == $size->id ? 'selected' : '' }}>
                                                {{ $size->name }} @if($size->code)({{ $size->code }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_size_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="product_variant_id" class="form-control-label">Varian Produk</label>
                                    <select class="form-select tom-select @error('product_variant_id') is-invalid @enderror" 
                                        id="product_variant_id" name="product_variant_id">
                                        <option value="">Pilih Varian Produk (Opsional)</option>
                                        @foreach($productVariants as $variant)
                                            <option value="{{ $variant->id }}" {{ old('product_variant_id') == $variant->id ? 'selected' : '' }}>
                                                {{ $variant->name ?? 'Tanpa Nama' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_variant_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-control-label">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" 
                                name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="initial_price" class="form-control-label">Harga Awal</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input class="form-control @error('initial_price') is-invalid @enderror" 
                                            type="number" id="initial_price" name="initial_price" 
                                            value="{{ old('initial_price') }}" 
                                            min="0" step="0.01" placeholder="0">
                                    </div>
                                    @error('initial_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="discount_percentage" class="form-control-label">Diskon (%)</label>
                                    <div class="input-group">
                                        <input class="form-control @error('discount_percentage') is-invalid @enderror" 
                                            type="number" id="discount_percentage" name="discount_percentage" 
                                            value="{{ old('discount_percentage') }}" 
                                            min="0" max="100" step="0.01" placeholder="0">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('discount_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-control-label">Harga Akhir</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="final_price" readonly value="0">
                            </div>
                            <small class="form-text text-muted">Harga akhir akan dihitung otomatis berdasarkan harga awal dan diskon</small>
                        </div>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                            <!-- Hidden input untuk memastikan is_active selalu terkirim -->
                            <input type="hidden" name="is_active_hidden" value="0">
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Setup is_active switch
        const isActiveCheckbox = document.getElementById('is_active');
        isActiveCheckbox.addEventListener('change', function() {
            console.log('Is Active changed:', this.checked);
        });
        
        // Inisialisasi Tom Select untuk seluruh dropdown
        let tomSelectInstances = {};
        
        // Konfigurasi Tom Select
        const tomSelectConfig = {
            plugins: ['clear_button'],
            allowEmptyOption: true,
            create: false,
            sortField: {
                field: 'text',
                direction: 'asc'
            },
            onInitialize: function(){
                // Pastikan class select asli tidak terlihat
                this.input.style.display = 'block';
                this.input.style.opacity = 0;
                this.input.style.position = 'absolute';
                this.input.style.left = '0px';
                this.input.style.height = '1px';
                this.input.style.width = '100%';
            }
        };
        
        // Inisialisasi Tom Select untuk Main Category
        tomSelectInstances.mainCategory = new TomSelect('#main_category_id', tomSelectConfig);
        
        // Inisialisasi Tom Select untuk Brand dengan custom render
        tomSelectInstances.brand = new TomSelect('#brand_id', {
            ...tomSelectConfig,
            onChange: function(value) {
                if (value) {
                    updateSubBrands(value);
                }
            }
        });
        
        // Inisialisasi Tom Select untuk Sub Brand
        tomSelectInstances.subBrand = new TomSelect('#sub_brand_id', {
            ...tomSelectConfig,
            onChange: function(value) {
                if (value) {
                    updateProductCategories(value);
                }
            }
        });
        
        // Inisialisasi Tom Select untuk Product Category
        tomSelectInstances.productCategory = new TomSelect('#product_category_id', {
            ...tomSelectConfig,
            onChange: function(value) {
                if (value) {
                    updateProductTypes(value);
                }
            }
        });
        
        // Inisialisasi Tom Select untuk Product Type
        tomSelectInstances.productType = new TomSelect('#product_type_id', tomSelectConfig);
        
        // Inisialisasi Tom Select untuk Product Size
        tomSelectInstances.productSize = new TomSelect('#product_size_id', tomSelectConfig);
        
        // Inisialisasi Tom Select untuk Product Variant
        tomSelectInstances.productVariant = new TomSelect('#product_variant_id', tomSelectConfig);

        // Fungsi untuk memperbarui dropdown Sub Brand berdasarkan Brand yang dipilih
        function updateSubBrands(brandId) {
            // Clear dan disable dropdown
            tomSelectInstances.subBrand.clear();
            tomSelectInstances.subBrand.clearOptions();
            
            // Tambahkan opsi default
            tomSelectInstances.subBrand.addOption({
                value: '',
                text: 'Pilih Sub Brand'
            });
            
            // Filter sub brands berdasarkan brand_id
            @foreach($subBrands as $subBrand)
            if ("{{ $subBrand->brand_id }}" === brandId) {
                tomSelectInstances.subBrand.addOption({
                    value: "{{ $subBrand->id }}",
                    text: "{{ $subBrand->name }}",
                    data: {
                        brand: "{{ $subBrand->brand_id }}"
                    }
                });
            }
            @endforeach
            
            tomSelectInstances.subBrand.refreshOptions(false);
            
            // Reset dropdown dependent
            tomSelectInstances.productCategory.clear();
            tomSelectInstances.productCategory.clearOptions();
            tomSelectInstances.productCategory.addOption({
                value: '',
                text: 'Pilih Kategori Produk'
            });
            tomSelectInstances.productCategory.refreshOptions(false);
            
            tomSelectInstances.productType.clear();
            tomSelectInstances.productType.clearOptions();
            tomSelectInstances.productType.addOption({
                value: '',
                text: 'Pilih Tipe Produk'
            });
            tomSelectInstances.productType.refreshOptions(false);
        }
        
        // Fungsi untuk memperbarui dropdown Product Category berdasarkan Sub Brand yang dipilih
        function updateProductCategories(subBrandId) {
            // Clear dan disable dropdown
            tomSelectInstances.productCategory.clear();
            tomSelectInstances.productCategory.clearOptions();
            
            // Tambahkan opsi default
            tomSelectInstances.productCategory.addOption({
                value: '',
                text: 'Pilih Kategori Produk'
            });
            
            // Filter product categories berdasarkan sub_brand_id
            @foreach($productCategories as $category)
            if ("{{ $category->sub_brand_id }}" === subBrandId) {
                tomSelectInstances.productCategory.addOption({
                    value: "{{ $category->id }}",
                    text: "{{ $category->name }}",
                    data: {
                        subbrand: "{{ $category->sub_brand_id }}"
                    }
                });
            }
            @endforeach
            
            tomSelectInstances.productCategory.refreshOptions(false);
            
            // Reset dropdown dependent
            tomSelectInstances.productType.clear();
            tomSelectInstances.productType.clearOptions();
            tomSelectInstances.productType.addOption({
                value: '',
                text: 'Pilih Tipe Produk'
            });
            tomSelectInstances.productType.refreshOptions(false);
        }
        
        // Fungsi untuk memperbarui dropdown Product Type berdasarkan Product Category yang dipilih
        function updateProductTypes(categoryId) {
            // Clear dan disable dropdown
            tomSelectInstances.productType.clear();
            tomSelectInstances.productType.clearOptions();
            
            // Tambahkan opsi default
            tomSelectInstances.productType.addOption({
                value: '',
                text: 'Pilih Tipe Produk'
            });
            
            // Filter product types berdasarkan product_category_id
            @foreach($productTypes as $type)
            if ("{{ $type->product_category_id }}" === categoryId) {
                tomSelectInstances.productType.addOption({
                    value: "{{ $type->id }}",
                    text: "{{ $type->name }}",
                    data: {
                        category: "{{ $type->product_category_id }}"
                    }
                });
            }
            @endforeach
            
            tomSelectInstances.productType.refreshOptions(false);
        }
        
        // Inisialisasi data awal jika ada data yang sudah tersimpan (setelah validasi gagal)
        const oldBrandId = "{{ old('brand_id') }}";
        const oldSubBrandId = "{{ old('sub_brand_id') }}";
        const oldCategoryId = "{{ old('product_category_id') }}";
        
        // Jika ada data brand yang tersimpan, trigger perubahan
        if (oldBrandId) {
            // Set nilai
            tomSelectInstances.brand.setValue(oldBrandId);
            
            // Update sub brands
            setTimeout(() => {
                updateSubBrands(oldBrandId);
                
                // Jika ada data sub brand yang tersimpan, trigger perubahan
                if (oldSubBrandId) {
                    setTimeout(() => {
                        tomSelectInstances.subBrand.setValue(oldSubBrandId);
                        updateProductCategories(oldSubBrandId);
                        
                        // Jika ada data kategori yang tersimpan, trigger perubahan
                        if (oldCategoryId) {
                            setTimeout(() => {
                                tomSelectInstances.productCategory.setValue(oldCategoryId);
                                updateProductTypes(oldCategoryId);
                                
                                // Set product type jika ada
                                const oldTypeId = "{{ old('product_type_id') }}";
                                if (oldTypeId) {
                                    setTimeout(() => {
                                        tomSelectInstances.productType.setValue(oldTypeId);
                                    }, 100);
                                }
                            }, 100);
                        }
                    }, 100);
                }
            }, 100);
        }
        
        // Pastikan nilai semua dropdown diisi dengan benar saat form disubmit
        document.getElementById('productForm').addEventListener('submit', function(e) {
            // Periksa apakah semua dropdown required sudah terisi
            const requiredSelects = [
                { id: 'main_category_id', name: 'Main Category' },
                { id: 'brand_id', name: 'Brand' },
                { id: 'sub_brand_id', name: 'Sub Brand' },
                { id: 'product_category_id', name: 'Kategori Produk' },
                { id: 'product_type_id', name: 'Tipe Produk' },
                { id: 'product_size_id', name: 'Ukuran Produk' }
            ];
                
            let hasErrors = false;
            let errorMessages = [];
            
        // Log is_active value untuk debugging
        console.log('Is Active (before submit):', document.getElementById('is_active').checked);
        
        // Calculate final price when initial price or discount changes
        const initialPriceInput = document.getElementById('initial_price');
        const discountInput = document.getElementById('discount_percentage');
        const finalPriceInput = document.getElementById('final_price');
        
        function calculateFinalPrice() {
            const initialPrice = parseFloat(initialPriceInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            
            const finalPrice = initialPrice * (1 - discount / 100);
            finalPriceInput.value = new Intl.NumberFormat('id-ID').format(Math.round(finalPrice));
        }
        
        initialPriceInput.addEventListener('input', calculateFinalPrice);
        discountInput.addEventListener('input', calculateFinalPrice);
            
            requiredSelects.forEach(field => {
                // Ambil nilai dari instance Tom Select
                const instance = tomSelectInstances[field.id.replace('_id', '')];
                const value = instance.getValue();
                
                // Log nilai ke konsol untuk debugging
                console.log(`${field.name} value:`, value);
                
                // Set nilai ke hidden input untuk memastikan data terkirim dengan benar
                if (!value) {
                    e.preventDefault();
                    hasErrors = true;
                    errorMessages.push(`${field.name} harus diisi`);
                    
                    // Tampilkan pesan error
                    const fieldElement = document.getElementById(field.id);
                    const parentElement = fieldElement.parentNode;
                    
                    // Tambahkan class bootstrap untuk invalid
                    const wrapper = parentElement.querySelector('.ts-wrapper');
                    if (wrapper) {
                        wrapper.classList.add('is-invalid');
                    }
                    
                    // Buat atau tampilkan pesan error jika belum ada
                    let errorElement = parentElement.querySelector('.invalid-feedback');
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'invalid-feedback';
                        errorElement.textContent = `${field.name} harus diisi.`;
                        parentElement.appendChild(errorElement);
                    } else {
                        errorElement.textContent = `${field.name} harus diisi.`;
                    }
                    
                    errorElement.style.display = 'block';
                }
            });
            
            if (hasErrors) {
                // Buat alert error dengan bootstrap
                const alertElement = document.createElement('div');
                alertElement.className = 'alert alert-danger mt-3';
                alertElement.innerHTML = '<strong>Mohon periksa kembali form Anda:</strong><ul>' + 
                    errorMessages.map(msg => `<li>${msg}</li>`).join('') + '</ul>';
                
                // Hapus alert sebelumnya jika ada
                const oldAlert = document.querySelector('form .alert-danger');
                if (oldAlert) {
                    oldAlert.remove();
                }
                
                // Tambahkan alert di awal form
                const formElement = document.getElementById('productForm');
                formElement.insertBefore(alertElement, formElement.firstChild);
                
                // Scroll ke atas
                window.scrollTo(0, 0);
            }
        });
        
        // Tambahkan event listener untuk menghapus class is-invalid saat nilai berubah
        for (const key in tomSelectInstances) {
            if (tomSelectInstances[key] && typeof tomSelectInstances[key].on === 'function') {
                tomSelectInstances[key].on('change', function() {
                    const fieldId = this.input.id;
                    const parentElement = document.getElementById(fieldId).parentNode;
                    
                    // Hapus class is-invalid
                    const wrapper = parentElement.querySelector('.ts-wrapper');
                    if (wrapper) {
                        wrapper.classList.remove('is-invalid');
                    }
                    
                    // Sembunyikan pesan error
                    const errorElement = parentElement.querySelector('.invalid-feedback');
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                });
            }
        }
    });
</script>
@endpush
@endsection