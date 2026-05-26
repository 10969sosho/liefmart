@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="ds-card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Detail Pelanggan</h6>
                    <div>
                        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card card-plain">
                                <div class="card-header pb-0">
                                    <h6 class="text-uppercase text-primary">Informasi Utama</h6>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">ID</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $customer->id }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Nama</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $customer->name }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Email</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $customer->email == '-' ? '-' : $customer->email }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Telepon</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $customer->phone }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">NPWP</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $customer->npwp ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card card-plain">
                                <div class="card-header pb-0">
                                    <h6 class="text-uppercase text-primary">Informasi Tambahan</h6>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">PIC</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $customer->pic_name }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Status</div>
                                        <div class="col-md-8">
                                            <span class="badge {{ $customer->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $customer->status_label }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Dibuat pada</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $customer->created_at->format('d M Y H:i') }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Diperbarui pada</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $customer->updated_at->format('d M Y H:i') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card card-plain">
                                <div class="card-header pb-0">
                                    <h6 class="text-uppercase text-primary">Alamat</h6>
                                </div>
                                <div class="card-body pt-3">
                                    <p class="text-sm">{{ $customer->address ?: 'Tidak ada alamat' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 