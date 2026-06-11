<div>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <style>
    :root {
        --cp-card-bg: #ffffff;
        --cp-card-border: #e5e7eb;
        --cp-header-bg: linear-gradient(to right, #f9fafb, #f3f4f6);
        --cp-header-text: #1f2937;
        --cp-body-bg: #ffffff;
        --cp-label-text: #374151;
        --cp-input-bg: #ffffff;
        --cp-input-border: #d1d5db;
        --cp-input-readonly-bg: #f9fafb;
        --cp-text: #1f2937;
        --cp-text-muted: #6b7280;
        --cp-text-subtle: #9ca3af;
        --cp-table-header-bg: #f9fafb;
        --cp-table-header-text: #4b5563;
        --cp-table-border: #e5e7eb;
        --cp-table-row-hover: #f9fafb;
        --cp-total-text: #4f46e5;
        --cp-btn-bg: #4f46e5;
        --cp-btn-hover: #4338ca;
        --cp-btn-text: #ffffff;
        --cp-discount-bg: rgba(59, 130, 246, 0.05);
        --cp-discount-border: rgba(59, 130, 246, 0.2);
        --cp-modal-overlay: rgba(0, 0, 0, 0.5);
        --cp-modal-bg: #ffffff;
        --cp-modal-border: #e5e7eb;
        --cp-empty-icon: #d1d5db;
        --cp-free-badge-bg: #f3f4f6;
        --cp-free-badge-text: #4b5563;
        --cp-discon-badge-bg: rgba(59, 130, 246, 0.1);
        --cp-discon-badge-text: #2563eb;
        --cp-delete-btn-bg: #fef2f2;
        --cp-delete-btn-text: #ef4444;
        --cp-delete-btn-hover-bg: #ef4444;
        --cp-delete-btn-hover-text: #ffffff;
    }
    .dark {
        --cp-card-bg: #1e1e2a;
        --cp-card-border: #2a2a3d;
        --cp-header-bg: linear-gradient(to right, #1e1e2a, #252538);
        --cp-header-text: #e5e7eb;
        --cp-body-bg: #1e1e2a;
        --cp-label-text: #d1d5db;
        --cp-input-bg: #252538;
        --cp-input-border: #374151;
        --cp-input-readonly-bg: #2a2a3d;
        --cp-text: #e5e7eb;
        --cp-text-muted: #9ca3af;
        --cp-text-subtle: #6b7280;
        --cp-table-header-bg: #252538;
        --cp-table-header-text: #9ca3af;
        --cp-table-border: #2a2a3d;
        --cp-table-row-hover: #252538;
        --cp-total-text: #818cf8;
        --cp-btn-bg: #6366f1;
        --cp-btn-hover: #818cf8;
        --cp-btn-text: #ffffff;
        --cp-discount-bg: rgba(59, 130, 246, 0.1);
        --cp-discount-border: rgba(59, 130, 246, 0.3);
        --cp-modal-overlay: rgba(0, 0, 0, 0.7);
        --cp-modal-bg: #1e1e2a;
        --cp-modal-border: #2a2a3d;
        --cp-empty-icon: #374151;
        --cp-free-badge-bg: #374151;
        --cp-free-badge-text: #9ca3af;
        --cp-discon-badge-bg: rgba(59, 130, 246, 0.2);
        --cp-discon-badge-text: #93c5fd;
        --cp-delete-btn-bg: rgba(239, 68, 68, 0.15);
        --cp-delete-btn-text: #f87171;
        --cp-delete-btn-hover-bg: #ef4444;
        --cp-delete-btn-hover-text: #ffffff;
    }
    .cp-card { background: var(--cp-card-bg); border-color: var(--cp-card-border); }
    .cp-header { background: var(--cp-header-bg); border-color: var(--cp-card-border); }
    .cp-header-text { color: var(--cp-header-text); }
    .cp-body { background: var(--cp-body-bg); }
    .cp-label { color: var(--cp-label-text); }
    .cp-input { background: var(--cp-input-bg); border-color: var(--cp-input-border); color: var(--cp-text); }
    .cp-input:focus { border-color: var(--cp-total-text); outline: none; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15); }
    .cp-input-readonly { background: var(--cp-input-readonly-bg); color: var(--cp-text-muted); }
    .cp-text { color: var(--cp-text); }
    .cp-text-muted { color: var(--cp-text-muted); }
    .cp-text-subtle { color: var(--cp-text-subtle); }
    .cp-table-header { background: var(--cp-table-header-bg); color: var(--cp-table-header-text); border-color: var(--cp-table-border); }
    .cp-table-cell { border-color: var(--cp-table-border); color: var(--cp-text); }
    .cp-table-row:hover { background: var(--cp-table-row-hover); }
    .cp-total-text { color: var(--cp-total-text); }
    .cp-btn { background: var(--cp-btn-bg); color: var(--cp-btn-text); }
    .cp-btn:hover { background: var(--cp-btn-hover); }
    .cp-discount-box { background: var(--cp-discount-bg); border-color: var(--cp-discount-border); }
    .cp-modal-overlay { background: var(--cp-modal-overlay); }
    .cp-modal-bg { background: var(--cp-modal-bg); border-color: var(--cp-modal-border); }
    .cp-empty-icon { color: var(--cp-empty-icon); }
    .cp-hr { border-color: var(--cp-card-border); }
    .ts-wrapper .ts-control { background: var(--cp-input-bg) !important; border-color: var(--cp-input-border) !important; color: var(--cp-text) !important; border-radius: 0.5rem !important; min-height: 40px; }
    .ts-wrapper .ts-control input { color: var(--cp-text) !important; }
    .ts-dropdown { background: var(--cp-card-bg) !important; border-color: var(--cp-card-border) !important; z-index: 9999 !important; }
    .ts-dropdown .option { color: var(--cp-text) !important; }
    .ts-dropdown .active { background: var(--cp-table-row-hover) !important; }
    .flash-highlight { background-color: #fef3c7 !important; transition: background-color 0.5s; }
    .flash-success { background-color: #d1fae5 !important; transition: background-color 0.5s; }
    .item-row { animation: fadeIn 0.3s ease-out forwards; }
    .item-row.deleting { animation: fadeOut 0.3s ease-in forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-10px); } }
    .dark input, .dark select, .dark textarea { color-scheme: dark; }
    .dark .ts-dropdown .option.active { background: #2a2a3d !important; }
    </style>

    <form onsubmit="return false;" id="formPenerimaan">
        <div class="space-y-6">
            <div class="rounded-xl shadow-sm border cp-card cp-body overflow-hidden">
                <div class="cp-header px-6 py-4 border-b cp-header-text">
                    <h5 class="font-semibold m-0"><i class="fas fa-info-circle text-indigo-500 me-2"></i>Informasi Utama</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium cp-label mb-1">Kode Penerimaan</label>
                            <input type="text" class="w-full rounded-lg border px-4 py-2.5 text-sm cp-input cp-input-readonly" id="kode_penerimaan" value="(Otomatis)" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium cp-label mb-1">Kategori Barang</label>
                            <input type="text" class="w-full rounded-lg border px-4 py-2.5 text-sm cp-input cp-input-readonly" value="{{ session('main_category_name') }}" readonly>
                            <input type="hidden" name="main_category_id" id="main_category_id" value="{{ session('main_category_id') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium cp-label mb-1">Kategori Pajak <span class="text-red-500">*</span></label>
                            <select class="w-full rounded-lg border px-4 py-2.5 text-sm cp-input" id="tax_category_id" name="tax_category_id" required>
                                <option value="" disabled selected>-- Pilih Kategori Pajak --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium cp-label mb-1">Nomor PO <span class="text-red-500">*</span></label>
                            <input type="text" class="w-full rounded-lg border px-4 py-2.5 text-sm cp-input" id="nomor_po" name="nomor_po" placeholder="Masukkan nomor PO" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium cp-label mb-1">Tanggal Penerimaan <span class="text-red-500">*</span></label>
                            <input type="date" class="w-full rounded-lg border px-4 py-2.5 text-sm cp-input" id="tanggal_penerimaan" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium cp-label mb-1">Metode Pembayaran <span class="text-red-500">*</span></label>
                            <select class="w-full rounded-lg border px-4 py-2.5 text-sm cp-input" id="metode_pembayaran" required>
                                <option value="Cash">Cash</option>
                                <option value="Jatuh Tempo">Jatuh Tempo</option>
                            </select>
                        </div>
                        <div id="jatuhTempoContainer" style="display: none;">
                            <label class="block text-sm font-medium cp-label mb-1">Tanggal Jatuh Tempo <span class="text-red-500">*</span></label>
                            <input type="date" class="w-full rounded-lg border px-4 py-2.5 text-sm cp-input" id="tanggal_jatuh_tempo">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium cp-label mb-1">Catatan</label>
                        <textarea class="w-full rounded-lg border px-4 py-2.5 text-sm cp-input" id="catatan" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
                    </div>
                </div>
            </div>

            <div class="rounded-xl shadow-sm border cp-card cp-body overflow-hidden">
                <div class="cp-header px-6 py-4 border-b cp-header-text">
                    <h5 class="font-semibold m-0"><i class="fas fa-box text-indigo-500 me-2"></i>Detail Barang</h5>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="tabelDetailBarang">
                        <thead class="cp-table-header border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Nama Barang</th>
                                <th class="px-4 py-3 text-center font-medium">Qty</th>
                                <th class="px-4 py-3 text-center font-medium">Satuan</th>
                                <th class="px-4 py-3 text-right font-medium">Harga</th>
                                <th class="px-4 py-3 text-right font-medium">Diskon</th>
                                <th class="px-4 py-3 text-right font-medium">Sub Total</th>
                                <th class="px-4 py-3 text-center font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabelDetailBarangBody">
                            <tr id="emptyRow">
                                <td colspan="7" class="px-4 py-12 text-center cp-text-muted">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 cp-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <p class="font-medium">Belum ada barang</p>
                                        <p class="text-xs">Tambahkan barang menggunakan form di bawah</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t cp-body" style="border-color: var(--cp-card-border);">
                    <div class="grid grid-cols-12 gap-3 items-end mb-3">
                        <div class="col-span-4">
                            <label class="block text-xs font-medium cp-label mb-1">Nama Barang <span class="text-red-500">*</span></label>
                            <select class="w-full rounded-lg border px-3 py-2 text-sm cp-input" id="barang_id">
                                <option value="" selected disabled>-- Pilih Barang --</option>
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-medium cp-label mb-1">Qty <span class="text-red-500">*</span></label>
                            <input type="number" class="w-full rounded-lg border px-3 py-2 text-sm text-center cp-input" id="qty" min="0.01" step="0.01" placeholder="0">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium cp-label mb-1">Satuan <span class="text-red-500">*</span></label>
                            <select class="w-full rounded-lg border px-3 py-2 text-sm cp-input" id="satuan_id">
                                <option value="" selected disabled>-- Satuan --</option>
                                @foreach(\App\Models\Satuan::where('is_active', true)->get() as $satuan)
                                    <option value="{{ $satuan->id }}">{{ $satuan->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium cp-label mb-1">Harga <span class="text-red-500">*</span></label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 cp-input cp-input-readonly text-sm" style="border-color: var(--cp-input-border);">Rp</span>
                                <input type="number" class="w-full rounded-r-lg border px-3 py-2 text-sm cp-input" id="harga_hpp" min="0" placeholder="0" style="border-left: 0;">
                            </div>
                        </div>
                        <div class="col-span-2">
                            <div class="flex items-center h-full pt-5">
                                <label class="flex items-center gap-2 text-sm cp-label cursor-pointer">
                                    <input type="checkbox" class="rounded cp-input" id="is_free" style="border-color: var(--cp-input-border);">
                                    <span>Barang Free</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-medium cp-label mb-1">&nbsp;</label>
                            <button type="button" class="w-full px-3 py-2 rounded-lg text-sm font-medium cp-btn transition" id="btnTambahBarang">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-3 rounded-lg border cp-discount-box mb-3">
                        <p class="text-xs font-medium cp-label mb-2">
                            <i class="fas fa-tags text-indigo-500 me-1"></i> Sistem Diskon (5 Level)
                            <span class="cp-text-muted font-normal"> — Isi hanya satu jenis per level</span>
                        </p>
                        <div class="grid grid-cols-5 gap-2">
                            @for($i = 1; $i <= 5; $i++)
                            <div>
                                <label class="block text-xs cp-text-muted">Level {{ $i }}</label>
                                <div class="flex gap-1">
                                    <input type="number" class="w-1/2 rounded border px-1 py-1.5 text-xs text-center cp-input discount-input" id="diskon_persen_{{ $i }}" min="0" max="100" step="0.01" placeholder="%">
                                    <input type="number" class="w-1/2 rounded border px-1 py-1.5 text-xs text-center cp-input discount-input" id="diskon_nominal_{{ $i }}" min="0" placeholder="Rp">
                                </div>
                            </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl shadow-sm border cp-card cp-body overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h5 class="font-semibold cp-text mb-0">Total Penerimaan</h5>
                            <p class="text-xs cp-text-muted m-0">Jumlah total biaya penerimaan barang</p>
                        </div>
                        <h3 class="text-2xl font-bold cp-total-text m-0" id="totalHargaDisplay">Rp 0</h3>
                    </div>
                    <hr class="cp-hr my-3">
                    <button type="submit" class="w-full py-3 rounded-xl font-semibold text-base shadow-sm cp-btn transition" id="btnSimpan">
                        <i class="fas fa-save me-2"></i> Simpan & Masukkan ke Unlocated
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="fixed inset-0 z-50 hidden" id="priceHistoryModal">
        <div class="absolute inset-0 cp-modal-overlay" onclick="closePriceHistory()"></div>
        <div class="relative mx-auto mt-20 max-w-3xl rounded-xl shadow-2xl cp-modal-bg border" style="border-color: var(--cp-modal-border);">
            <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color: var(--cp-modal-border);">
                <h5 class="font-semibold cp-text m-0" id="priceHistoryModalLabel">
                    <i class="fas fa-history me-2"></i>History Harga
                </h5>
                <button type="button" class="cp-text-muted hover:cp-text text-2xl leading-none" onclick="closePriceHistory()">&times;</button>
            </div>
            <div class="p-6 cp-text" id="priceHistoryContent">
                <div class="text-center py-8 cp-text-muted">Pilih barang untuk melihat history harga</div>
            </div>
        </div>
    </div>

    <script>
    function initFormPenerimaan() {
        if (window._penerimaanFormInitialized) return;
        window._penerimaanFormInitialized = true;

        const mainCategoryId = (document.getElementById('main_category_id')?.value) || '2';
        const taxCategorySelect = document.getElementById('tax_category_id');
        const barangSelect = document.getElementById('barang_id');
        const satuanSelect = document.getElementById('satuan_id');
        const hargaInput = document.getElementById('harga_hpp');
        const qtyInput = document.getElementById('qty');
        const metodePembayaranSelect = document.getElementById('metode_pembayaran');
        const jatuhTempoContainer = document.getElementById('jatuhTempoContainer');
        const tanggalJatuhTempoInput = document.getElementById('tanggal_jatuh_tempo');
        const isFreeCheckbox = document.getElementById('is_free');
        const btnTambahBarang = document.getElementById('btnTambahBarang');
        const tabelDetailBarang = document.getElementById('tabelDetailBarang');
        const emptyRow = document.getElementById('emptyRow');
        const formPenerimaan = document.getElementById('formPenerimaan');

        if (!tabelDetailBarang || !btnTambahBarang) {
            setTimeout(initFormPenerimaan, 500);
            return;
        }

        const diskonPersenInputs = [
            document.getElementById('diskon_persen_1'), document.getElementById('diskon_persen_2'),
            document.getElementById('diskon_persen_3'), document.getElementById('diskon_persen_4'),
            document.getElementById('diskon_persen_5')
        ];
        const diskonNominalInputs = [
            document.getElementById('diskon_nominal_1'), document.getElementById('diskon_nominal_2'),
            document.getElementById('diskon_nominal_3'), document.getElementById('diskon_nominal_4'),
            document.getElementById('diskon_nominal_5')
        ];

        let detailItems = [];
        let counter = 0;

        function formatRupiah(amount) {
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
        }
        function formatRupiahTotal(amount) {
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(amount));
        }

        async function parseJsonResponse(response) {
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const data = await response.json();
                if (!response.ok) {
                    let message = data.message || 'HTTP error! Status: ' + response.status;
                    if (data.errors && typeof data.errors === 'object') {
                        const firstKey = Object.keys(data.errors)[0];
                        const firstError = firstKey ? data.errors[firstKey] : null;
                        if (Array.isArray(firstError) && firstError[0]) message = firstError[0];
                        else if (typeof firstError === 'string' && firstError) message = firstError;
                    }
                    throw new Error(message);
                }
                return data;
            }
            const status = response.status;
            if (status === 419 || status === 401 || status === 403) throw new Error('Sesi login habis. Silakan refresh.');
            throw new Error('Respon server bukan JSON (HTTP ' + status + ').');
        }

        let taxCategoryTomSelect = new TomSelect(taxCategorySelect, {
            placeholder: '-- Pilih Kategori Pajak --', allowEmptyOption: true,
            sortField: { field: 'text', direction: 'asc' }, dropdownParent: 'body', closeAfterSelect: true
        });

        let barangTomSelect = new TomSelect(barangSelect, {
            placeholder: '-- Pilih Barang --', allowEmptyOption: true,
            sortField: { field: 'text', direction: 'asc' }, dropdownParent: 'body', closeAfterSelect: true,
            onChange: function(value) {
                if (!value) return;
                const selectedOption = this.options[value];
                if (selectedOption) {
                    const defaultSatuanId = selectedOption.default_satuan_id;
                    if (defaultSatuanId && defaultSatuanId !== 'null' && defaultSatuanId !== '') {
                        satuanTomSelect.setValue(defaultSatuanId);
                    }
                    const hargaValue = selectedOption.harga_hpp;
                    if (hargaValue && hargaValue !== '0' && hargaValue !== 'null') {
                        const hargaNum = parseFloat(hargaValue);
                        if (!isNaN(hargaNum) && hargaNum > 0 && !isFreeCheckbox.checked) {
                            hargaInput.value = hargaNum;
                        }
                    }
                    suggestLastPrice(value);
                    qtyInput.focus();
                }
            }
        });

        let satuanTomSelect = new TomSelect(satuanSelect, {
            placeholder: '-- Pilih Satuan --', allowEmptyOption: true,
            sortField: { field: 'text', direction: 'asc' }, dropdownParent: 'body', closeAfterSelect: true
        });

        function loadTaxCategories(mainCategoryId) {
            if (!mainCategoryId) return;
            taxCategoryTomSelect.clear(); taxCategoryTomSelect.clearOptions();
            taxCategoryTomSelect.addOption({ value: '', text: 'Loading...' });
            taxCategoryTomSelect.disable();

            fetch('/api/tax-categories?main_category_id=' + mainCategoryId, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(parseJsonResponse)
            .then(data => {
                taxCategoryTomSelect.clear(); taxCategoryTomSelect.clearOptions();
                taxCategoryTomSelect.addOption({ value: '', text: '-- Pilih Kategori Pajak --' });
                let filteredCategories = data.tax_categories || [];
                if (mainCategoryId == 2) filteredCategories = filteredCategories.filter(cat => cat.id == 3 || cat.id == 4);
                filteredCategories.forEach(category => {
                    taxCategoryTomSelect.addOption({ value: category.id, text: category.name + ' (' + category.tax_percentage + '%)' });
                });
                taxCategoryTomSelect.enable();
                if (filteredCategories.length > 0) taxCategoryTomSelect.setValue(filteredCategories[0].id);
            })
            .catch(error => {
                console.error('Error:', error);
                taxCategoryTomSelect.clear(); taxCategoryTomSelect.clearOptions();
                taxCategoryTomSelect.addOption({ value: '', text: 'Error: ' + error.message });
                taxCategoryTomSelect.disable();
            });
        }
        loadTaxCategories(mainCategoryId);

        if (mainCategoryId) {
            barangTomSelect.clear(); barangTomSelect.clearOptions();
            barangTomSelect.addOption({ value: '', text: 'Loading...' });

            fetch('{{ route("penerimaan.get-products") }}?main_category_id=' + mainCategoryId, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(parseJsonResponse)
            .then(data => {
                barangTomSelect.clear(); barangTomSelect.clearOptions();
                barangTomSelect.addOption({ value: '', text: '-- Pilih Barang --' });
                const items = Array.isArray(data) ? data : (data.data || []);
                items.forEach(product => {
                    barangTomSelect.addOption({
                        value: product.id,
                        text: product.text || product.name,
                        harga_hpp: product.harga_hpp || product.price || 0,
                        default_satuan_id: product.default_satuan_id || null
                    });
                });
            })
            .catch(error => {
                console.error('Error:', error);
                barangTomSelect.clear(); barangTomSelect.clearOptions();
                barangTomSelect.addOption({ value: '', text: 'Error loading products' });
            });
        }

        metodePembayaranSelect.addEventListener('change', function() {
            if (this.value === 'Jatuh Tempo') {
                jatuhTempoContainer.style.display = 'block';
                tanggalJatuhTempoInput.setAttribute('required', 'required');
            } else {
                jatuhTempoContainer.style.display = 'none';
                tanggalJatuhTempoInput.removeAttribute('required');
            }
        });

        isFreeCheckbox.addEventListener('change', function() {
            if (this.checked) {
                hargaInput.value = '0'; hargaInput.setAttribute('readonly', 'readonly');
                diskonPersenInputs.forEach(i => { i.value = '0'; i.setAttribute('readonly', 'readonly'); });
                diskonNominalInputs.forEach(i => { i.value = '0'; i.setAttribute('readonly', 'readonly'); });
            } else {
                hargaInput.removeAttribute('readonly');
                diskonPersenInputs.forEach(i => i.removeAttribute('readonly'));
                diskonNominalInputs.forEach(i => i.removeAttribute('readonly'));
            }
        });

        btnTambahBarang.addEventListener('click', function() {
            if (!barangTomSelect.getValue()) { barangTomSelect.focus(); return; }
            const qtyVal = parseFloat(qtyInput.value) || 0;
            if (!qtyInput.value || qtyVal <= 0) { qtyInput.focus(); return; }
            if (!satuanTomSelect.getValue()) { satuanTomSelect.focus(); return; }
            if (!isFreeCheckbox.checked && (!hargaInput.value || parseFloat(hargaInput.value) <= 0)) { hargaInput.focus(); return; }

            const barangId = barangTomSelect.getValue();
            const barangItem = barangTomSelect.getItem(barangId);
            const barangText = barangItem ? barangItem.textContent : '';
            const qty = qtyVal;
            const satuanId = satuanTomSelect.getValue();
            const satuanItem = satuanTomSelect.getItem(satuanId);
            const satuanText = satuanItem ? satuanItem.textContent : '';
            const harga = isFreeCheckbox.checked ? 0 : (parseFloat(hargaInput.value) || 0);
            const isFree = isFreeCheckbox.checked;

            const diskonPersenValues = diskonPersenInputs.map(i => { const v = parseFloat(i?.value); return isNaN(v) ? 0 : v; });
            const diskonNominalValues = diskonNominalInputs.map(i => { const v = parseFloat(i?.value); return isNaN(v) ? 0 : v; });

            let subtotal = Math.round(qty * harga * 100) / 100;
            if (!isFree) {
                for (let i = 0; i < 5; i++) {
                    if (diskonPersenValues[i] > 0) subtotal = Math.round((subtotal - subtotal * diskonPersenValues[i] / 100) * 100) / 100;
                    else if (diskonNominalValues[i] > 0) subtotal = Math.round((subtotal - diskonNominalValues[i]) * 100) / 100;
                }
            } else { subtotal = 0; }

            btnTambahBarang.innerHTML = '<i class="fas fa-check"></i>';
            btnTambahBarang.style.background = '#059669';
            setTimeout(() => { btnTambahBarang.innerHTML = '<i class="fas fa-plus"></i>'; btnTambahBarang.style.background = ''; }, 1000);

            if (emptyRow.style.display !== 'none') { emptyRow.style.display = 'none'; }

            let discountBadgesHTML = '';
            for (let i = 0; i < 5; i++) {
                if (diskonPersenValues[i] > 0) discountBadgesHTML += '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium me-1" style="background:var(--cp-discon-badge-bg);color:var(--cp-discon-badge-text)">D' + (i + 1) + ': ' + diskonPersenValues[i] + '%</span>';
                else if (diskonNominalValues[i] > 0) discountBadgesHTML += '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium me-1" style="background:var(--cp-discon-badge-bg);color:var(--cp-discon-badge-text)">D' + (i + 1) + ': Rp ' + formatRupiah(diskonNominalValues[i]) + '</span>';
            }
            if (discountBadgesHTML === '' && !isFree) discountBadgesHTML = '-';

            const newRow = document.createElement('tr');
            newRow.id = 'item-' + counter;
            newRow.className = 'item-row cp-table-cell cp-table-row border-b';
            newRow.innerHTML = '<td class="px-4 py-3"><p class="font-medium cp-text mb-0">' + barangText + '</p></td>'
                + '<td class="px-4 py-3 text-center">' + qty + '</td>'
                + '<td class="px-4 py-3 text-center">' + satuanText + '</td>'
                + '<td class="px-4 py-3 text-right">' + (isFree ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" style="background:var(--cp-free-badge-bg);color:var(--cp-free-badge-text)">Free</span>' : 'Rp ' + formatRupiah(harga)) + '</td>'
                + '<td class="px-4 py-3 text-right">' + (isFree ? '-' : discountBadgesHTML) + '</td>'
                + '<td class="px-4 py-3 text-right font-medium">' + (isFree ? 'Free' : 'Rp ' + formatRupiah(subtotal)) + '</td>'
                + '<td class="px-4 py-3 text-center"><button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-lg transition" data-id="' + counter + '" onclick="removeItem(this)" style="background:var(--cp-delete-btn-bg);color:var(--cp-delete-btn-text)" onmouseover="this.style.background=\'var(--cp-delete-btn-hover-bg)\';this.style.color=\'var(--cp-delete-btn-hover-text)\'" onmouseout="this.style.background=\'var(--cp-delete-btn-bg)\';this.style.color=\'var(--cp-delete-btn-text)\'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';

            tabelDetailBarang.querySelector('tbody').appendChild(newRow);

            detailItems.push({
                id: counter,
                barang_id: parseInt(barangId) || 0,
                qty: qty,
                satuan_id: parseInt(satuanId) || 0,
                harga_hpp: harga,
                diskon_persen_1: diskonPersenValues[0] || 0,
                diskon_persen_2: diskonPersenValues[1] || 0,
                diskon_persen_3: diskonPersenValues[2] || 0,
                diskon_persen_4: diskonPersenValues[3] || 0,
                diskon_persen_5: diskonPersenValues[4] || 0,
                diskon_nominal_1: diskonNominalValues[0] || 0,
                diskon_nominal_2: diskonNominalValues[1] || 0,
                diskon_nominal_3: diskonNominalValues[2] || 0,
                diskon_nominal_4: diskonNominalValues[3] || 0,
                diskon_nominal_5: diskonNominalValues[4] || 0,
                is_free: isFree ? 1 : 0,
                subtotal: subtotal || 0
            });
            counter++;
            updateTotal();
            resetInputs();
        });

        window.removeItem = function(btn) {
            const row = btn.closest('tr');
            const id = parseInt(btn.dataset.id);
            row.classList.add('deleting');
            setTimeout(() => {
                row.remove();
                detailItems = detailItems.filter(item => item.id !== id);
                updateTotal();
                if (detailItems.length === 0) emptyRow.style.display = '';
            }, 300);
        };

        function updateTotal() {
            let total = 0;
            detailItems.forEach(item => { total += item.subtotal || 0; });
            document.getElementById('totalHargaDisplay').textContent = 'Rp ' + formatRupiahTotal(total);
        }

        function resetInputs() {
            barangTomSelect.clear();
            satuanTomSelect.clear();
            qtyInput.value = '';
            hargaInput.value = '';
            diskonPersenInputs.forEach(i => i.value = '');
            diskonNominalInputs.forEach(i => i.value = '');
            isFreeCheckbox.checked = false;
            hargaInput.removeAttribute('readonly');
            diskonPersenInputs.forEach(i => i.removeAttribute('readonly'));
            diskonNominalInputs.forEach(i => i.removeAttribute('readonly'));
            setTimeout(() => barangTomSelect.focus(), 100);
        }

        window.suggestLastPrice = function(productId) {
            if (!productId) return;
            fetch('/penerimaan/price-history/' + productId, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(parseJsonResponse)
            .then(data => {
                if (data.success && data.history.length > 0 && !hargaInput.value && !isFreeCheckbox.checked) {
                    const last = data.history[0];
                    if (!last.is_free) {
                        hargaInput.value = last.harga;
                    }
                }
            })
            .catch(e => console.error(e));
        };

        window.closePriceHistory = function() {
            document.getElementById('priceHistoryModal').classList.add('hidden');
        };

        formPenerimaan.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (detailItems.length === 0) { alert('Belum ada barang ditambahkan!'); return; }

            const btn = document.getElementById('btnSimpan');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin inline-block w-5 h-5 me-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Menyimpan...';

            try {
                const headerData = new URLSearchParams();
                headerData.append('main_category_id', mainCategoryId);
                headerData.append('tax_category_id', taxCategoryTomSelect.getValue());
                headerData.append('nomor_po', document.getElementById('nomor_po').value);
                headerData.append('tanggal_penerimaan', document.getElementById('tanggal_penerimaan').value);
                headerData.append('metode_pembayaran', metodePembayaranSelect.value);
                if (metodePembayaranSelect.value === 'Jatuh Tempo' && tanggalJatuhTempoInput.value) {
                    headerData.append('tanggal_jatuh_tempo', tanggalJatuhTempoInput.value);
                }
                headerData.append('catatan', document.getElementById('catatan').value);

                const headerRes = await fetch('{{ route("penerimaan.create-header") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: headerData.toString()
                });
                const headerResult = await parseJsonResponse(headerRes);
                if (!headerResult.success) throw new Error(headerResult.message || 'Gagal membuat header');

                const penerimaanId = headerResult.penerimaan_id;

                const detailRes = await fetch('{{ route("penerimaan.store-batch-details", ["id" => "__ID__"]) }}'.replace('__ID__', penerimaanId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ items: detailItems })
                });
                const detailResult = await parseJsonResponse(detailRes);
                if (!detailResult.success) throw new Error(detailResult.message || 'Gagal menyimpan detail');

                const finalRes = await fetch('{{ route("penerimaan.finalize", ["id" => "__ID__"]) }}'.replace('__ID__', penerimaanId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                });
                const finalResult = await parseJsonResponse(finalRes);
                if (!finalResult.success) throw new Error(finalResult.message || 'Gagal finalize');

                window.location.href = '{{ route("filament.admin.resources.penerimaans.index") }}';
            } catch (error) {
                alert('Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i> Simpan & Masukkan ke Unlocated';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormPenerimaan);
    } else {
        initFormPenerimaan();
    }
    </script>
</div>