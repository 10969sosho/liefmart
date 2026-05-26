<div class="modal fade" id="calculationModal" tabindex="-1" aria-labelledby="calculationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="calculationModalLabel">
                    <i class="bi bi-calculator me-2"></i>Detail Perhitungan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Product Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary">Informasi Produk</h6>
                        <table class="table table-sm">
                            <tr><td width="30%"><strong>SKU:</strong></td><td id="modal-sku"></td></tr>
                            <tr><td><strong>Nama Produk:</strong></td><td id="modal-product-name"></td></tr>
                            <tr><td><strong>Nama di Platform:</strong></td><td id="modal-platform-name"></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Informasi Order</h6>
                        <table class="table table-sm">
                            <tr><td width="30%"><strong>No. Order:</strong></td><td id="modal-order-number"></td></tr>
                            <tr><td><strong>Platform:</strong></td><td id="modal-platform"></td></tr>
                            <tr><td><strong>Tanggal:</strong></td><td id="modal-order-date"></td></tr>
                        </table>
                    </div>
                </div>

                <!-- Calculation Details -->
                <h6 class="text-primary">Perhitungan Finansial</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Nilai</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>QTY (Platform)</strong></td><td class="text-end" id="modal-platform-qty"></td><td class="small text-muted">Jumlah yang diorder di platform</td></tr>
                            <tr><td><strong>QTY (Master Barang)</strong></td><td class="text-end" id="modal-qty"></td><td class="small text-muted">Platform order qty × mapping qty</td></tr>
                            <tr><td><strong>Jumlah Masuk Pembayaran</strong></td><td class="text-end" id="modal-payment-amount"></td><td class="small text-muted">Total saldo masuk dari order</td></tr>
                            <tr><td><strong>Jumlah Masuk Pembayaran - PPN</strong></td><td class="text-end" id="modal-payment-amount-no-ppn"></td><td class="small text-muted">Jumlah masuk pembayaran ÷ 1.11</td></tr>
                            <tr class="table-info"><td><strong>% Distribusi Revenue</strong></td><td class="text-end" id="modal-proportion"></td><td class="small text-muted">Proporsi nilai produk dalam total order</td></tr>
                            <tr><td><strong>Masuk Pembayaran per Produk</strong></td><td class="text-end" id="modal-payment-per-product"></td><td class="small text-muted">(Jumlah masuk pembayaran × % distribusi) ÷ QTY master barang</td></tr>
                            <tr><td><strong>Masuk Pembayaran per Produk - PPN</strong></td><td class="text-end" id="modal-payment-per-product-no-ppn"></td><td class="small text-muted">Masuk pembayaran per produk ÷ 1.11</td></tr>
                            <tr><td><strong>Harga Modal (COGS)</strong></td><td class="text-end" id="modal-capital-per-unit"></td><td class="small text-muted">Total capital ÷ quantity</td></tr>
                            <tr class="table-success"><td><strong>Profit per PCS</strong></td><td class="text-end" id="modal-profit-per-pcs"></td><td class="small text-muted">Masuk pembayaran produk-PPN - harga modal</td></tr>
                            <tr class="table-warning"><td><strong>Gross Profit Total</strong></td><td class="text-end" id="modal-profit-total"></td><td class="small text-muted">Profit per PCS × QTY master produk</td></tr>
                            <tr class="table-info"><td><strong>Margin per PCS (%)</strong></td><td class="text-end" id="modal-margin-per-pcs"></td><td class="small text-muted">(Profit per PCS ÷ Masuk pembayaran produk-PPN) × 100%</td></tr>
                            <tr class="table-info"><td><strong>Margin per Item (%)</strong></td><td class="text-end" id="modal-margin-per-item"></td><td class="small text-muted">(Gross Profit Total ÷ (Masuk pembayaran produk-PPN × QTY master produk)) × 100%</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

