@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Input Manual Data Keuangan {{ $platformLabel }}</span>
                    <a href="{{ route('finance.tiktok.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            @if($errors->has('order_id'))
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small><i class="fas fa-info-circle me-1"></i>Pastikan Anda telah memilih <strong>Nomor Pesanan</strong> dari dropdown di atas.</small>
                                </div>
                            @endif
                        </div>
                    @endif

                    <form action="{{ route('finance.tiktok.manual-store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="order_id" class="form-label">Nomor Pesanan <span class="text-danger">*</span></label>
                                <select class="form-select" id="order_id" name="order_id" required>
                                    <option value="">-- Pilih Nomor Pesanan --</option>
                                    @php
                                        $platformModel = App\Models\Platform::whereRaw('LOWER(name) LIKE ?', ['%tiktok%lamourad%'])->first();
                                        $tiktokPlatformId = $platformModel ? $platformModel->id : 99;
                                        
                                        // Get orders without financial transactions
                                        $availableOrders = App\Models\Order::where('platform_id', $tiktokPlatformId)
                                            ->whereDoesntHave('tiktokFinancialTransactions')
                                            ->orderBy('tanggal', 'desc')
                                            ->get();
                                        
                                        // Get selected order ID from request or old input
                                        $selectedOrderId = request('order_id') ?? old('order_id');
                                        $selectedOrder = isset($order) ? $order : null;
                                        
                                        // If there's a selected order ID but not in $order variable, try to find it
                                        if ($selectedOrderId && !$selectedOrder) {
                                            $selectedOrder = App\Models\Order::where('platform_id', $tiktokPlatformId)
                                                ->find($selectedOrderId);
                                        }
                                    @endphp
                                    @foreach($availableOrders as $orderItem)
                                        <option value="{{ $orderItem->id }}" {{ ($selectedOrderId == $orderItem->id) ? 'selected' : '' }}>{{ $orderItem->order_number }} - {{ $orderItem->customer_name }} - {{ $orderItem->tanggal ? $orderItem->tanggal->format('d/m/Y') : 'N/A' }}</option>
                                    @endforeach
                                    @if($selectedOrder && !$availableOrders->contains('id', $selectedOrder->id))
                                        <option value="{{ $selectedOrder->id }}" selected>{{ $selectedOrder->order_number }} - {{ $selectedOrder->customer_name }} - {{ $selectedOrder->tanggal ? $selectedOrder->tanggal->format('d/m/Y') : 'N/A' }} @if($selectedOrder->tiktokFinancialTransactions()->exists())(Sudah memiliki transaksi)@endif</option>
                                    @endif
                                </select>
                                @error('order_id')
                                    <div class="text-danger small mt-1">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                                <div class="form-text" id="order_id_help">
                                    <i class="fas fa-info-circle me-1"></i>Pilih nomor pesanan dari daftar yang tersedia
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="tanggal_masuk_pembayaran" class="form-label">Tanggal Masuk Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_masuk_pembayaran" name="tanggal_masuk_pembayaran" value="{{ old('tanggal_masuk_pembayaran', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="hari_masuk_pembayaran" class="form-label">Hari Masuk Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-select" id="hari_masuk_pembayaran" name="hari_masuk_pembayaran" required>
                                    <option value="Senin" {{ old('hari_masuk_pembayaran') == 'Senin' ? 'selected' : '' }}>Senin</option>
                                    <option value="Selasa" {{ old('hari_masuk_pembayaran') == 'Selasa' ? 'selected' : '' }}>Selasa</option>
                                    <option value="Rabu" {{ old('hari_masuk_pembayaran') == 'Rabu' ? 'selected' : '' }}>Rabu</option>
                                    <option value="Kamis" {{ old('hari_masuk_pembayaran') == 'Kamis' ? 'selected' : '' }}>Kamis</option>
                                    <option value="Jumat" {{ old('hari_masuk_pembayaran') == 'Jumat' ? 'selected' : '' }}>Jumat</option>
                                    <option value="Sabtu" {{ old('hari_masuk_pembayaran') == 'Sabtu' ? 'selected' : '' }}>Sabtu</option>
                                    <option value="Minggu" {{ old('hari_masuk_pembayaran') == 'Minggu' ? 'selected' : '' }}>Minggu</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="saldo_masuk" class="form-label">Jumlah Masuk Pembayaran <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="saldo_masuk" name="saldo_masuk" value="{{ old('saldo_masuk', 0) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon1" class="form-label">Biaya Admin</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon1" name="nominal_diskon1" value="{{ old('nominal_diskon1', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon2" class="form-label">Affiliate Commission</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon2" name="nominal_diskon2" value="{{ old('nominal_diskon2', 0) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon3" class="form-label">Seller Shipping Fee + SFP Service Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon3" name="nominal_diskon3" value="{{ old('nominal_diskon3', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon4" class="form-label">Biaya 4 (Voucher Xtra Service Fee)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon4" name="nominal_diskon4" value="{{ old('nominal_diskon4', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon5" class="form-label">Biaya 5 (Cashback Fee)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon5" name="nominal_diskon5" value="{{ old('nominal_diskon5', 0) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon6" class="form-label">Biaya 6</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon6" name="nominal_diskon6" value="{{ old('nominal_diskon6', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon7" class="form-label">Biaya 7</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon7" name="nominal_diskon7" value="{{ old('nominal_diskon7', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon8" class="form-label">Biaya 8</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon8" name="nominal_diskon8" value="{{ old('nominal_diskon8', 0) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon9" class="form-label">Biaya 9</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon9" name="nominal_diskon9" value="{{ old('nominal_diskon9', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon10" class="form-label">Biaya 10</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon10" name="nominal_diskon10" value="{{ old('nominal_diskon10', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon11" class="form-label">Biaya 11</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon11" name="nominal_diskon11" value="{{ old('nominal_diskon11', 0) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon12" class="form-label">Biaya 12</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon12" name="nominal_diskon12" value="{{ old('nominal_diskon12', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="adjustment" class="form-label">Adjustment</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="adjustment" name="adjustment" value="{{ old('adjustment', 0) }}">
                                </div>
                                <div class="form-text">Nilai positif akan menambah nominal fix, nilai negatif akan mengurangi nominal fix</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Data
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-wrapper .ts-control {
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .ts-wrapper.multi .ts-control {
        padding: calc(0.375rem - 1px) 0.75rem;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .no-results {
        padding: 0.5rem;
        color: #856404;
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }
</style>
@endpush

@push('scripts')
<!-- Tom Select JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let tomSelectInstance = null;
        
        // Initialize Tom Select for order selection
        try {
            const selectElement = document.getElementById('order_id');
            if (selectElement) {
                // Store all options before TomSelect initialization
                const allOptions = Array.from(selectElement.options).map(opt => ({
                    value: opt.value,
                    text: opt.text,
                    selected: opt.selected
                }));
                
                // Check if TomSelect is loaded
                if (typeof TomSelect !== 'undefined') {
                    tomSelectInstance = new TomSelect('#order_id', {
                        create: false,
                        sortField: {
                            field: "text",
                            direction: "asc"
                        },
                        placeholder: "Pilih Nomor Pesanan",
                        render: {
                            no_results: function(data, escape) {
                                // Custom message when no results found
                                try {
                                    const currentValue = this.inputValue;
                                    // Ensure currentValue is a string
                                    const searchValue = (currentValue != null) ? String(currentValue).trim() : '';
                                    if (searchValue !== '') {
                                        return '<div class="no-results">Nomor pesanan "' + escape(searchValue) + '" tidak ditemukan. Pastikan nomor pesanan sudah terdaftar dan belum memiliki transaksi finansial.</div>';
                                    }
                                } catch (e) {
                                    console.error('Error in no_results render:', e);
                                }
                                return '<div class="no-results">Tidak ada hasil ditemukan</div>';
                            },
                            option: function(data, escape) {
                                return '<div>' + escape(data.text) + '</div>';
                            }
                        },
                        onChange: function(value) {
                            // Update help text when order is selected
                            const helpText = document.getElementById('order_id_help');
                            if (value && helpText) {
                                const selectedOption = selectElement.querySelector(`option[value="${value}"]`);
                                if (selectedOption) {
                                    const orderNumber = selectedOption.textContent.split(' - ')[0];
                                    helpText.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i>Nomor pesanan terpilih: <strong>' + orderNumber + '</strong>';
                                } else {
                                    // If option not found in DOM, try to get from TomSelect
                                    const option = this.options[value];
                                    if (option) {
                                        const orderNumber = option.text.split(' - ')[0];
                                        helpText.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i>Nomor pesanan terpilih: <strong>' + orderNumber + '</strong>';
                                    }
                                }
                            } else if (helpText) {
                                helpText.innerHTML = '<i class="fas fa-info-circle me-1"></i>Pilih nomor pesanan dari daftar yang tersedia';
                            }
                        }
                    });
                    console.log('TomSelect initialized successfully');
                    
                    // Set initial help text if order is pre-selected
                    const initialValue = selectElement.value;
                    if (initialValue) {
                        const selectedOption = selectElement.querySelector(`option[value="${initialValue}"]`);
                        if (selectedOption) {
                            const helpText = document.getElementById('order_id_help');
                            if (helpText) {
                                const orderNumber = selectedOption.textContent.split(' - ')[0];
                                helpText.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i>Nomor pesanan terpilih: <strong>' + orderNumber + '</strong>';
                            }
                        }
                    }
                } else {
                    console.error('TomSelect library not loaded. Loading it dynamically...');
                    // Try to load it dynamically
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js';
                    script.onload = function() {
                        console.log('TomSelect loaded dynamically');
                        tomSelectInstance = new TomSelect('#order_id', {
                            create: false,
                            sortField: {
                                field: "text",
                                direction: "asc"
                            },
                            placeholder: "Pilih Nomor Pesanan",
                            render: {
                                no_results: function(data, escape) {
                                    try {
                                        const currentValue = this.inputValue;
                                        // Ensure currentValue is a string
                                        const searchValue = (currentValue != null) ? String(currentValue).trim() : '';
                                        if (searchValue !== '') {
                                            return '<div class="no-results">Nomor pesanan "' + escape(searchValue) + '" tidak ditemukan. Pastikan nomor pesanan sudah terdaftar dan belum memiliki transaksi finansial.</div>';
                                        }
                                    } catch (e) {
                                        console.error('Error in no_results render:', e);
                                    }
                                    return '<div class="no-results">Tidak ada hasil ditemukan</div>';
                                },
                                option: function(data, escape) {
                                    return '<div>' + escape(data.text) + '</div>';
                                }
                            },
                            onChange: function(value) {
                                const helpText = document.getElementById('order_id_help');
                                if (value && helpText) {
                                    const selectedOption = selectElement.querySelector(`option[value="${value}"]`);
                                    if (selectedOption) {
                                        const orderNumber = selectedOption.textContent.split(' - ')[0];
                                        helpText.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i>Nomor pesanan terpilih: <strong>' + orderNumber + '</strong>';
                                    }
                                } else if (helpText) {
                                    helpText.innerHTML = '<i class="fas fa-info-circle me-1"></i>Pilih nomor pesanan dari daftar yang tersedia';
                                }
                            }
                        });
                    };
                    document.head.appendChild(script);
                }
            } else {
                console.error('Element #order_id not found');
            }
        } catch (error) {
            console.error('Error initializing TomSelect:', error);
        }
        
        // Form validation before submit
        const form = document.querySelector('form[action*="manual-store"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const orderIdSelect = document.getElementById('order_id');
                let orderIdValue = null;
                
                // Get value from TomSelect if it exists, otherwise from select element
                if (tomSelectInstance && tomSelectInstance.getValue) {
                    orderIdValue = tomSelectInstance.getValue();
                } else if (orderIdSelect) {
                    orderIdValue = orderIdSelect.value;
                }
                
                // Validate order_id
                if (!orderIdValue || orderIdValue === '') {
                    e.preventDefault();
                    alert('Silakan pilih Nomor Pesanan terlebih dahulu!');
                    
                    // Focus on the select element
                    if (orderIdSelect) {
                        orderIdSelect.focus();
                        // Scroll to the select element
                        orderIdSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    
                    return false;
                }
                
                // Ensure the value is set in the select element before submit
                if (orderIdSelect && orderIdSelect.value !== orderIdValue) {
                    orderIdSelect.value = orderIdValue;
                }
                
                return true;
            });
        }
        
        // Set day of week automatically when date changes
        const dateInput = document.getElementById('tanggal_masuk_pembayaran');
        const daySelect = document.getElementById('hari_masuk_pembayaran');
        
        if (dateInput && daySelect) {
            dateInput.addEventListener('change', function() {
                const date = new Date(this.value);
                if (!isNaN(date.getTime())) {
                    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    const dayName = days[date.getDay()];
                    
                    // Select the correct option
                    for (let i = 0; i < daySelect.options.length; i++) {
                        if (daySelect.options[i].value === dayName) {
                            daySelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            });
            
            // Trigger the change event initially to set the day
            dateInput.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush 