<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Kode Penerimaan</th>
            <th>Nomor PO</th>
            <th>Produk</th>
            <th>Lokasi</th>
            <th>Status</th>
            <th>Tanggal Penerimaan</th>
            <th>Qty Penerimaan</th>
            <th>Qty Tersedia</th>
        </tr>
    </thead>
    <tbody>
        @foreach($unlocatedItems as $index => $item)
        <tr @if($item->is_free) style="background-color: #FFECE5;" @endif>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->penerimaan->kode_penerimaan }}</td>
            <td>{{ $item->penerimaan->nomor_po }}</td>
            <td>{{ $item->product->name }}</td>
            <td>{{ $item->penerimaan->lokasi->nama ?? 'UNLOCATED' }}</td>
            <td>{{ $item->is_free ? 'FREE' : 'Normal' }}</td>
            <td>{{ \Carbon\Carbon::parse($item->penerimaan->tanggal_penerimaan)->format('d-m-Y') }}</td>
            <td>{{ $item->qty }}</td>
            <td>{{ $item->remaining_qty }}</td>
        </tr>
        @endforeach
    </tbody>
</table> 