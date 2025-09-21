@extends('layouts.app')

@section('title', 'Daftar Rekening Bank')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i> 
                <strong>Informasi:</strong> Rekening bank yang ditandai sebagai "Aktif" akan ditampilkan pada invoice saat dicetak.
                Hanya satu rekening bank yang dapat diaktifkan dalam sistem.
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-university text-primary mr-2"></i> Daftar Rekening Bank
                        </h5>
                        <a href="{{ route('bank-accounts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Rekening Bank
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th width="50" class="text-center">No</th>
                                    <th>Nama Bank</th>
                                    <th>Nomor Rekening</th>
                                    <th>Atas Nama</th>
                                    <th width="100" class="text-center">Status</th>
                                    <th>Deskripsi</th>
                                    <th width="180" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bankAccounts as $index => $account)
                                    <tr class="{{ $account->is_active ? 'table-success' : '' }}">
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td><strong>{{ $account->bank_name }}</strong></td>
                                        <td>{{ $account->account_number }}</td>
                                        <td>{{ $account->account_name }}</td>
                                        <td class="text-center">
                                            @if ($account->is_active)
                                                <span class="badge badge-success p-2"><i class="fas fa-check-circle mr-1"></i> Aktif</span>
                                            @else
                                                <span class="badge badge-secondary p-2"><i class="fas fa-times-circle mr-1"></i> Tidak Aktif</span>
                                            @endif
                                        </td>
                                        <td>{{ $account->description ?? '-' }}</td>
                                        <td>
                                            <div class="btn-group d-flex justify-content-center">
                                                @if (!$account->is_active)
                                                    <form action="{{ route('bank-accounts.set-active', $account) }}" method="POST" class="mr-1">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success" title="Jadikan Aktif">
                                                            <i class="fas fa-check-circle"></i> Aktifkan
                                                        </button>
                                                    </form>
                                                @endif

                                                <a href="{{ route('bank-accounts.edit', $account) }}" class="btn btn-info mr-1" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <form action="{{ route('bank-accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus rekening bank {{ $account->bank_name }} - {{ $account->account_number }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="fas fa-university text-muted mb-2" style="font-size: 3rem;"></i>
                                                <p class="mb-0">Belum ada data rekening bank</p>
                                                <a href="{{ route('bank-accounts.create') }}" class="btn btn-primary btn-sm mt-3">
                                                    <i class="fas fa-plus"></i> Tambah Rekening Bank
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    vertical-align: middle;
}
.btn-group .btn {
    display: flex;
    align-items: center;
    justify-content: center;
}
.badge {
    font-size: 85%;
}
</style>
@endsection 