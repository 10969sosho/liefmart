@extends('layouts.app')

@section('content')
    <script>
        // Immediate execution script to enforce table height
        (function() {
            console.log("Immediate table fix running");
            document.addEventListener('DOMContentLoaded', function() {
                // Force table height constraint
                const tableContainer = document.querySelector('.table-responsive');
                if (tableContainer) {
                    tableContainer.style.maxHeight = '500px';
                    tableContainer.style.overflowY = 'auto';
                    console.log("Applied immediate table height fix");
                }
            });
        })();
    </script>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pindahkan Barang ke Gudang A</h3>
                    </div>
                    <form action="{{ route('warehouse.store') }}" method="POST" id="transfer-form">
                        @csrf
                        <div class="card-body">
                            @if (session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto; border: 1px solid #dee2e6;">
                                <table class="table table-bordered table-striped wide-table mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 50px; position: sticky; top: 0; background-color: #f8f9fa; z-index: 1;">
                                                <input type="checkbox" id="check-all">
                                            </th>
                                            <th style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 1;">Nama Produk</th>
                                            <th style="width: 150px; position: sticky; top: 0; background-color: #f8f9fa; z-index: 1;">PO</th>
                                            <th style="width: 150px; position: sticky; top: 0; background-color: #f8f9fa; z-index: 1;">Jumlah</th>
                                            <th style="width: 120px; position: sticky; top: 0; background-color: #f8f9fa; z-index: 1;">Satuan</th>
                                            <th style="width: 180px; position: sticky; top: 0; background-color: #f8f9fa; z-index: 1;">Tanggal Expired</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($items as $item)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="items[{{ $item->id }}][selected]"
                                                        value="1" class="item-checkbox">
                                                    <input type="hidden"
                                                        name="items[{{ $item->id }}][penerimaan_detail_id]"
                                                        value="{{ $item->id }}">
                                                </td>
                                                <td>{{ $item->product->name }}</td>
                                                <td>{{ $item->penerimaan->nomor_po ?? '-' }}</td>
                                                <td>
                                                    <input type="number" name="items[{{ $item->id }}][qty]"
                                                        class="form-control form-control-sm qty-input"
                                                        value="{{ $item->remaining_qty }}" min="0.01"
                                                        max="{{ $item->remaining_qty }}" step="0.01" disabled>
                                                </td>
                                                <td>{{ $item->satuan->name }}</td>
                                                <td>
                                                    <input type="text" name="items[{{ $item->id }}][expired_date]"
                                                        class="form-control form-control-sm expired-input" 
                                                        placeholder="dd/mm/yyyy" disabled>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">Tidak ada barang untuk dipindahkan
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                                <i class="fas fa-save"></i> Simpan Perpindahan
                            </button>
                            <a href="{{ route('warehouse.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Include jQuery before your scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <script>
        console.log("Create page script loaded");
        
        // Monitor table size
        function logTableDimensions() {
            const table = document.querySelector('.wide-table');
            const container = document.querySelector('.table-responsive');
            if (table && container) {
                console.log("Table dimensions:", {
                    tableHeight: table.offsetHeight,
                    tableWidth: table.offsetWidth,
                    containerHeight: container.offsetHeight,
                    containerWidth: container.offsetWidth,
                    containerScrollHeight: container.scrollHeight,
                    containerMaxHeight: container.style.maxHeight
                });
            }
        }
        
        // Log dimensions on load and every 2 seconds
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Create page DOM loaded");
            logTableDimensions();
            
            // Log dimensions periodically
            setInterval(logTableDimensions, 2000);
            
            // Periksa apakah jQuery tersedia
            if (typeof jQuery === 'undefined') {
                console.error('jQuery tidak ditemukan! Pastikan jQuery dimuat sebelum script ini.');
                return; // Hentikan eksekusi jika jQuery tidak tersedia
            }
            
            // Gunakan jQuery setelah dipastikan tersedia
            jQuery(function($) {
                // Set tanggal hari ini sebagai default untuk input tanggal expired
                const today = new Date();
                const todayFormatted = formatDateDDMMYYYY(today);
                $('.expired-input').val(todayFormatted);

                // Checkbox di header untuk centang semua
                $('#check-all').change(function() {
                    const isChecked = $(this).prop('checked');
                    $('.item-checkbox').prop('checked', isChecked);
                    toggleInputs();
                    
                    // Jika dicentang semua, fokus ke input qty pertama
                    if (isChecked) {
                        $('.qty-input:first').focus();
                    }
                });

                // Checkbox per baris
                $('.item-checkbox').change(function() {
                    toggleInputs();
                    
                    // Jika checkbox dicentang, fokus ke input qty di baris yang sama
                    if ($(this).is(':checked')) {
                        $(this).closest('tr').find('.qty-input').focus();
                    }
                });

                // Validasi input qty agar tidak melebihi max
                $('.qty-input').on('input', function() {
                    const max = parseFloat($(this).attr('max'));
                    const val = parseFloat($(this).val());

                    if (val > max) {
                        $(this).val(max);
                        alert('Jumlah tidak boleh melebihi ' + max);
                    }
                });

                // Fungsi untuk enable/disable inputs berdasarkan status checkbox
                function toggleInputs() {
                    $('.item-checkbox').each(function() {
                        const $row = $(this).closest('tr');
                        const $qtyInput = $row.find('.qty-input');
                        const $expiredInput = $row.find('.expired-input');
                        const isChecked = $(this).is(':checked');

                        // Enable/disable inputs berdasarkan status checkbox
                        $qtyInput.prop('disabled', !isChecked);
                        $expiredInput.prop('disabled', !isChecked);

                        // Pastikan ada nilai default untuk tanggal expired saat dicentang
                        if (isChecked && !$expiredInput.val()) {
                            $expiredInput.val(todayFormatted);
                        }
                    });

                    // Aktifkan/nonaktifkan tombol submit
                    const checkedBoxes = $('.item-checkbox:checked').length;
                    $('#submit-btn').prop('disabled', checkedBoxes === 0);
                }

                // Inisialisasi status inputs saat halaman dimuat
                toggleInputs();

                // Flag to track form submission status
                let isSubmitting = false;

                // Validasi form sebelum submit
                $('#transfer-form').submit(function(e) {
                    // Prevent multiple submissions
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }

                    let valid = true;
                    let message = '';

                    $('.item-checkbox:checked').each(function() {
                        const $row = $(this).closest('tr');
                        const $qtyInput = $row.find('.qty-input');
                        const $expiredInput = $row.find('.expired-input');
                        const productName = $row.find('td:nth-child(2)').text();

                        if (!$qtyInput.val() || parseFloat($qtyInput.val()) <= 0) {
                            message = 'Jumlah untuk ' + productName + ' harus diisi dan lebih dari 0';
                            valid = false;
                            return false;
                        }

                        if (parseFloat($qtyInput.val()) > parseFloat($qtyInput.attr('max'))) {
                            message = 'Jumlah untuk ' + productName + ' tidak boleh melebihi ' +
                                $qtyInput.attr('max');
                            valid = false;
                            return false;
                        }

                        if (!$expiredInput.val()) {
                            message = 'Tanggal expired untuk ' + productName + ' harus diisi';
                            valid = false;
                            return false;
                        }

                        // Validasi format tanggal dd/mm/yyyy
                        const datePattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
                        const dateValue = $expiredInput.val();
                        if (!datePattern.test(dateValue)) {
                            message = 'Format tanggal expired untuk ' + productName + ' harus dd/mm/yyyy (contoh: 25/12/2024)';
                            valid = false;
                            return false;
                        }

                        // Validasi tanggal yang valid
                        const dateParts = dateValue.match(datePattern);
                        if (dateParts) {
                            const day = parseInt(dateParts[1], 10);
                            const month = parseInt(dateParts[2], 10);
                            const year = parseInt(dateParts[3], 10);
                            
                            // Validasi range tanggal
                            if (day < 1 || day > 31 || month < 1 || month > 12 || year < 1900 || year > 2100) {
                                message = 'Tanggal expired untuk ' + productName + ' tidak valid';
                                valid = false;
                                return false;
                            }
                            
                            // Validasi tanggal yang benar-benar ada
                            const testDate = new Date(year, month - 1, day);
                            if (testDate.getDate() !== day || testDate.getMonth() !== month - 1 || testDate.getFullYear() !== year) {
                                message = 'Tanggal expired untuk ' + productName + ' tidak valid (contoh: 29/02/2024 tidak valid)';
                                valid = false;
                                return false;
                            }
                        }
                    });

                    if (!valid) {
                        e.preventDefault();
                        alert(message);
                        return false;
                    }

                    // If validation passes, convert dates to ISO format before submission
                    if (valid) {
                        // Convert dd/mm/yyyy to yyyy-mm-dd for backend
                        $('.item-checkbox:checked').each(function() {
                            const $row = $(this).closest('tr');
                            const $expiredInput = $row.find('.expired-input');
                            const dateValue = $expiredInput.val();
                            
                            if (dateValue) {
                                const dateParts = dateValue.split('/');
                                if (dateParts.length === 3) {
                                    const isoDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
                                    $expiredInput.val(isoDate);
                                }
                            }
                        });
                        
                        isSubmitting = true;
                        const $submitBtn = $('#submit-btn');
                        const originalHtml = $submitBtn.html();
                        
                        $submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                        
                        // Enable the button for actual submission
                        $submitBtn.prop('disabled', false);
                        
                        // If for some reason the form doesn't submit within 10 seconds, reset
                        setTimeout(function() {
                            if (isSubmitting) {
                                isSubmitting = false;
                                $submitBtn.html(originalHtml);
                                toggleInputs(); // Reset the disabled state based on checkboxes
                            }
                        }, 10000);
                        
                        return true;
                    }
                });
                
                // Handle the case when user navigates back to the page
                $(window).on('pageshow', function(event) {
                    if (event.originalEvent.persisted) {
                        // Page was loaded from cache (user pressed back button)
                        isSubmitting = false;
                        $('#submit-btn').prop('disabled', $('.item-checkbox:checked').length === 0);
                        toggleInputs(); // Re-apply disabled state to inputs
                    }
                });
            });
        });
    </script>
@endpush

